<?php

namespace RabbitCMS\Carrot\Eloquent;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * Class Validation.
 *
 * @mixin Eloquent
 */
trait Validation
{
    use \RabbitCMS\Carrot\Support\Validation;

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
     * @inheritdoc
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->fireModelEvent('invalidated') !== false) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Get validation data.
     *
     * @return array
     */
    protected function validationData()
    {
        return $this->attributesToArray();
    }
}
