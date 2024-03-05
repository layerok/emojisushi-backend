<?php namespace System\Classes;

/**
 * DriverBehavior base class.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class DriverBehavior extends ModelBehavior
{
    use \System\Traits\ConfigMaker;

    /**
     * @var mixed fieldConfig is extra configuration for the converter type.
     */
    protected $fieldConfig;

    /**
     * __construct
     */
    public function __construct($model = null)
    {
        parent::__construct($model);

        // Parse the config
        $this->configPath = $this->guessConfigPathFrom($this);
        $this->fieldConfig = $this->makeConfig($this->defineFormFields());

        if ($model) {
            $this->initDriverHost($model);
        }
    }

    /**
     * initDriverHost when the converter is first created.
     * @param \Model $host
     */
    public function initDriverHost($host)
    {
    }

    /**
     * validateDriverHost validates the host data before saving, this method
     * can throw a ValidationException for invalid fields. For example:
     *
     *     throw new \ValidationException(['max_weight' => 'Max weight cannot be less than Min weight!']);
     *
     * @param \Model $host
     */
    public function validateDriverHost($host)
    {
    }

    /**
     * defineFormFields for extra field configuration for the shipping type.
     */
    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    /**
     * getHostObject returns the host object with configuration.
     * @return \Shop\Models\CurrencyRate
     */
    public function getHostObject()
    {
        return $this->model;
    }

    /**
     * getFieldConfig returns the field configuration used by this model.
     */
    public function getFieldConfig()
    {
        return $this->fieldConfig;
    }
}
