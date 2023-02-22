<?php namespace Backend\Models\ImportModel;

use League\Csv\Reader as CsvReader;
use League\Csv\Statement as CsvStatement;
use Backend\Behaviors\ImportExportController\TranscodeFilter;

/**
 * DecodesCsv format for import
 */
trait DecodesCsv
{
    /**
     * decodeArrayValueForCsv
     */
    protected function decodeArrayValueForCsv($value, $delimeter = '|')
    {
        if (starts_with($value, 'base64:')) {
            return json_decode(base64_decode(substr($value, strlen('base64:'))), true);
        }

        if (strpos($value, $delimeter) === false) {
            return [$value];
        }

        $data = preg_split('~(?<!\\\)' . preg_quote($delimeter, '~') . '~', $value);
        $newData = [];

        foreach ($data as $_value) {
            $newData[] = str_replace('\\'.$delimeter, $delimeter, $_value);
        }

        return $newData;
    }

    /**
     * processImportDataAsCsv
     */
    protected function processImportDataAsCsv($filePath, $matches, $options)
    {
        // Parse options
        $defaultOptions = [
            'firstRowTitles' => true,
            'delimiter' => null,
            'enclosure' => null,
            'escape' => null,
            'encoding' => null
        ];

        $options = array_merge($defaultOptions, $options);

        // Read CSV
        $reader = CsvReader::createFromPath($filePath, 'r');

        if ($options['delimiter'] !== null) {
            $reader->setDelimiter($options['delimiter']);
        }

        if ($options['enclosure'] !== null) {
            $reader->setEnclosure($options['enclosure']);
        }

        if ($options['escape'] !== null) {
            $reader->setEscape($options['escape']);
        }

        if (
            $options['encoding'] !== null &&
            $reader->supportsStreamFilterOnRead()
        ) {
            $reader->addStreamFilter(sprintf(
                '%s%s:%s',
                TranscodeFilter::FILTER_NAME,
                strtolower($options['encoding']),
                'utf-8'
            ));
        }

        // Create reader statement
        $stmt = (new CsvStatement)
            ->where(function (array $row) {
                // Filter out empty rows
                return count($row) > 1 || reset($row) !== null;
            })
        ;

        if ($options['firstRowTitles']) {
            $stmt = $stmt->offset(1);
        }

        $result = [];
        $contents = $stmt->process($reader);

        foreach ($contents as $row) {
            $result[] = $this->processCsvImportRow($row, $matches);
        }

        return $result;
    }

    /**
     * processCsvImportRow converts a single row of CSV data to the column map
     * @return array
     */
    protected function processCsvImportRow($rowData, $matches)
    {
        $newRow = [];

        foreach ($matches as $columnIndex => $dbNames) {
            $value = array_get($rowData, $columnIndex);
            foreach ((array) $dbNames as $dbName) {
                $newRow[$dbName] = $value;
            }
        }

        return $newRow;
    }
}
