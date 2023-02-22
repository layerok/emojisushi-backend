<?php namespace Backend\Models\ExportModel;

use League\Csv\Writer as CsvWriter;
use SplTempFileObject;

/**
 * EncodesCsv format for export
 */
trait EncodesCsv
{
    /**
     * encodeArrayValueForCsv
     */
    protected function encodeArrayValueForCsv(array $data, $delimeter = '|')
    {
        // Multi dimension arrays have no choice but to base64 encode
        if (count($data) !== count($data, COUNT_RECURSIVE)) {
            return 'base64:' . base64_encode(json_encode($data));
        }

        // Implode single dimension array as a string
        $newData = [];
        foreach ($data as $value) {
            $newData[] = str_replace($delimeter, '\\'.$delimeter, $value);
        }

        return implode($delimeter, $newData);
    }

    /**
     * processExportDataAsCsv returns the export data as a CSV string
     */
    protected function processExportDataAsCsv($columns, $results, $options)
    {
        // Parse options
        $options = array_merge([
            'firstRowTitles' => true,
            'savePath' => null,
            'useOutput' => false,
            'fileName' => null,
            'delimiter' => null,
            'enclosure' => null,
            'escape' => null
        ], $options);

        // Prepare CSV
        if ($options['savePath']) {
            $csv = CsvWriter::createFromPath($options['savePath'], 'w+');
        }
        else {
            $csv = CsvWriter::createFromFileObject(new SplTempFileObject);
        }

        $csv->setOutputBOM(CsvWriter::BOM_UTF8);

        if ($options['delimiter'] !== null) {
            $csv->setDelimiter($options['delimiter']);
        }

        if ($options['enclosure'] !== null) {
            $csv->setEnclosure($options['enclosure']);
        }

        if ($options['escape'] !== null) {
            $csv->setEscape($options['escape']);
        }

        // Add headers
        if ($options['firstRowTitles']) {
            $headers = $this->getColumnHeaders($columns);
            $csv->insertOne($headers);
        }

        // Add records
        foreach ($results as $result) {
            $data = $this->matchDataToColumns($result, $columns);
            $csv->insertOne($data);
        }

        // Output
        if ($options['useOutput']) {
            $csv->output($options['fileName']);
            return;
        }

        // Saved to file
        if ($options['savePath']) {
            return;
        }

        return $csv->toString();
    }
}
