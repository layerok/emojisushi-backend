<?php namespace Backend\Behaviors\RelationController;

/**
 * HasExtraConfig implements state for the relation controller
 */
trait HasExtraConfig
{
    /**
     * @var array extraConfig provided by the relationRender method
     */
    protected $extraConfig;

    /**
     * @var string extraConfigString for rendering as a variable
     */
    protected $extraConfigString;

    /**
     * @var bool bumpSessionKeys is used to create a new session for forms
     */
    protected $bumpSessionKeys = false;

    /**
     * prepareExtraConfig returns extra config with the relation chain, encoded
     */
    protected function prepareExtraConfig()
    {
        if ($this->bumpSessionKeys) {
            $this->relationSessionKey = $this->sessionKey;
            $this->sessionKey = str_random(40);
        }

        $extraConfig = $this->extraConfig;
        $extraConfig['chain'][] = $this->field;
        $extraConfig['manageIds'][$this->field] = $this->manageId;
        $extraConfig['sessionKeys'][$this->field] = [$this->sessionKey, $this->relationSessionKey];

        $this->extraConfigString = json_encode($extraConfig);
    }

    /**
     * setExtraConfig
     */
    protected function setExtraConfig($config)
    {
        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        if (is_array($config)) {
            $this->extraConfig = $config;
        }
    }

    /**
     * applyExtraConfig
     */
    protected function applyExtraConfig($field = null)
    {
        if (!$field) {
            $field = $this->field;
        }

        $config = $this->extraConfig;

        $originalConfig = $this->originalConfig->{$field} ?? null;
        if (!$config || !$originalConfig) {
            return;
        }

        // readOnlyDefault is used by the relation widget to apply a soft
        // default value, i.e. where a value is otherwise unspecified.
        // In order of application: 1. default 2. config 3. render
        if (
            array_key_exists('readOnlyDefault', $config) &&
            !array_key_exists('readOnly', $config) &&
            !array_key_exists('readOnly', $originalConfig)
        ) {
            $config['readOnly'] = $config['readOnlyDefault'];
        }

        $parsedConfig = array_only($config, ['readOnly']);
        $parsedConfig['view'] = array_only($config, ['recordUrl', 'recordOnClick']);

        $this->originalConfig->{$field} = array_replace_recursive(
            $originalConfig,
            $parsedConfig
        );
    }
}
