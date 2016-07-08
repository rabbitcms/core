<?php

namespace RabbitCMS\Carrot\Eloquent;

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Validation\Validator;

/**
 * Class Validation.
 *
 * @mixin Eloquent
 */
trait Validation
{
    /**
     * @var bool
     */
    protected $autoValidation = true;

    /**
     * The "booting" method of the trait.
     */
    public static function bootValidation()
    {
        static::saving(
            function (Eloquent $model) {
                /* @var Eloquent|Validation $model */
                return $model->autoValidation ? $model->validate() : true;
            }
        );
    }

    /**
     * Validate the class instance.
     *
     * @return void
     */
    public function validate()
    {
        $instance = $this->getValidatorInstance();

        if (!$instance->passes()) {
            $this->failedValidation($instance);
        }
    }

    /**
     * Get the validator instance for the request.
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        $container = Container::getInstance();
        $factory = $container->make(ValidationFactory::class);

        if (method_exists($this, 'validator')) {
            return $container->call([$this, 'validator'], compact('factory'));
        }

        return $factory->make(
            $this->attributesToArray(),
            $container->call([$this, 'validationRules']),
            $this->validationMessages(),
            $this->validationAttributes()
        );
    }

    /**
     * Set custom messages for validator errors.
     *
     * @return array
     */
    public function validationMessages()
    {
        return [];
    }

    /**
     * Set custom attributes for validator errors.
     *
     * @return array
     */
    public function validationAttributes()
    {
        return [];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Validation\Validator $validator
     *
     * @throws \Illuminate\Contracts\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->fireModelEvent('invalidated') !== false) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Disable auto validation on save.
     */
    public function disableAutoValidation()
    {
        $this->setAutoValidation(false);
    }

    /**
     * Set auto validation flag.
     *
     * @param bool $value
     */
    public function setAutoValidation($value)
    {
        $this->autoValidation = $value;
    }

    /**
     * Validation rules.
     *
     * @return array
     */
    abstract protected function validationRules();
}