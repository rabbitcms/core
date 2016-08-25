<?php
namespace RabbitCMS\Carrot\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * Trait Validation.
 */
trait Validation
{
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
            $this->validationData(),
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
     * Validation rules.
     *
     * @return array
     */
    abstract public function validationRules();

    /**
     * Handle a failed validation attempt.
     *
     * @param  Validator $validator
     *
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator);
    }
}
