<?php namespace Tailor\Classes;

use Yaml;
use Exception;
use Tailor\Classes\Blueprint\EntryBlueprint;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * BlueprintVerifier super class responsible for validating blueprints
 *
 * @todo List
 * - Duplicate field names (including mixins)
 * - Duplicate handles
 * - Duplicate UUIDs
 * - Missing source references
 * - Reserved field names
 *
 * @method static BlueprintVerifier instance()
 *
 * @package october\tailor
 * @author Alexey Bobkov, Samuel Georges
 */
class BlueprintVerifier
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * verifyBlueprint
     */
    public function verifyBlueprint(Blueprint $blueprint)
    {
        $this->validateYamlSyntax($blueprint);
        $this->validateSupportedTypes($blueprint);
        $this->validateFieldset($blueprint);
    }

    /**
     * validateYamlSyntax checks the YAML syntax and parses attributes
     */
    protected function validateYamlSyntax(Blueprint $blueprint)
    {
        try {
            $blueprint->attributes = (array) Yaml::parse($blueprint->content);
        }
        catch (ParseException $ex) {
            $this->yamlToBlueprintException($blueprint, $ex);
        }
    }

    /**
     * validateSupportedTypes checks for valid blueprint types
     */
    protected function validateSupportedTypes(Blueprint $blueprint)
    {
        $supportedTypes = ['entry', 'stream', 'structure', 'single', 'mixin', 'global'];

        if (in_array($blueprint->type, $supportedTypes)) {
            return;
        }

        $lineNo = $this->findLineFromKeyValPair($blueprint->content, 'type', $blueprint->type);

        $typeAsString = implode(', ', $supportedTypes);
        throw new BlueprintException($blueprint, "Type must be one of: {$typeAsString}.", $lineNo);
    }

    /**
     * validateFieldset
     */
    protected function validateFieldset(Blueprint $blueprint)
    {
        $fields = $blueprint->fields ?? [];

        if ($blueprint instanceof EntryBlueprint && is_array($blueprint->groups)) {
            foreach ($blueprint->groups as $group) {
                $fields += $group['fields'] ?? [];
            }
        }

        $fieldset = FieldManager::instance()->makeFieldset(['fields' => $fields]);
        $fieldset->validate();
    }

    /**
     * findLineFromKeyValPair
     */
    protected function findLineFromKeyValPair($content, $key, $val)
    {
        $content = PHP_EOL.$content;
        $regex = '/\n\s*'.preg_quote($key, '/').':\s*'.preg_quote($val, '/').'\s*\n/';

        if (preg_match($regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
            $charPos = $matches[0][1];

            // Find line number from char position
            list($before) = str_split($content, $charPos);
            return strlen($before) - strlen(str_replace("\n", "", $before)) + 1;
        }

        return 0;
    }

    /**
     * yamlToBlueprintException is a workaround to access protected property `rawMessage`
     */
    protected function yamlToBlueprintException($blueprint, $ex)
    {
        $lineNo = $ex->getParsedLine();
        $ex->setSnippet('');
        $ex->setParsedLine(-1);

        throw new BlueprintException(
            $blueprint,
            $ex->getMessage(),
            $lineNo,
            $ex
        );
    }
}
