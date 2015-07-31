<?php namespace RabbitCMS\Carrot\Support;

class Layout
{
    protected $title;

    /**
     * @param null $title
     * @return mixed
     */
    public function title($title = null)
    {
        if ($title !== null) {
            $this->title = $title;
        }

        return $this->title;
    }
}