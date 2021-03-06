<?php

declare(strict_types=1);

/*
 * This file is part of Eloquent Auto Presenter.
 *
 * (c) Brian Faust <hello@basecode.sh>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Artisanry\AutoPresenter;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Presenter implements UrlRoutable
{
    /**
     * @var \Artisanry\AutoPresenter\Presentable
     */
    protected $model;

    /**
     * @var \Artisanry\AutoPresenter\Decorator
     */
    protected $decorator;

    /**
     * @param \Artisanry\AutoPresenter\Presentable $model
     * @param \Artisanry\AutoPresenter\Decorator   $decorator
     */
    public function __construct(Presentable $model, Decorator $decorator)
    {
        $this->model = $model;
        $this->decorator = $decorator;
    }

    /**
     * Return the raw model or an attribute.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function raw(string $key = null)
    {
        return $raw ? $this->model->{$raw} : $this->model;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        $value = $this->model->{$key};

        if ($value instanceof Presentable || $value instanceof Collection || is_array($value)) {
            return $this->decorator->decorate($value);
        }

        return $this->present($key);
    }

    /**
     * Present ("view") an attribute.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function present(string $key)
    {
        return ($method = $this->getMethod($key))
                ? call_user_func([$this, $method], $this->model->{$key})
                : $this->model->{$key};
    }

    /**
     * Get the methode name for an attribute.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getMethod(string $key): string
    {
        if (method_exists($this, $method = $key)
         || method_exists($this, $method = Str::snake($key))
         || method_exists($this, $method = Str::camel($key))
        ) {
            return $method;
        }
    }

    /**
     * [__call description].
     *
     * @param string $method
     * @param array  $params
     *
     * @return mixed
     */
    public function __call(string $method, array $params)
    {
        return call_user_func_array([$this->model, $method], $params);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->model->__isset($key);
    }

    /**
     * Cast to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = collect($this->model->toArray());

        return $attributes->map(function ($_, $key) {
            return $this->present($key);
        })->all();
    }

    /**
     * Cast to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Get the value of the model's route key.
     *
     * @return mixed
     */
    public function getRouteKey(): string
    {
        return $this->model->getRouteKey();
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return $this->model->getRouteKeyName();
    }
}
