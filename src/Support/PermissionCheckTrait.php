<?php namespace RabbitCMS\Carrot\Support;

use Doctrine\Common\Annotations\AnnotationReader;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use RabbitCMS\Carrot\Annotation\Permissions;
use RabbitCMS\Carrot\Contracts\HasAccessEntity;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class PermissionCheckTrait
 *
 * @package RabbitCMS\Carrot\Support
 * @mixin Controller
 */
trait PermissionCheckTrait
{
    /**
     * Execute an action on the controller.
     *
     * @param  string $method
     * @param  array  $parameters
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        /**
         * @var HasAccessEntity|PermissionsTrait|null $user
         * @var Permissions                           $annotation
         */
        try {
            $user = \Auth::guard(property_exists($this, 'guard') ? $this->guard : null)->user();
            $reader = new AnnotationReader();
            $class = new \ReflectionClass($this);
            $annotation = $reader->getClassAnnotation($class, Permissions::class);

            if ($user instanceof PermissionsTrait && !empty($annotation)) {
                if (!$user->hasAccess($annotation->permissions, $annotation->all)) {
                    throw new AccessDeniedHttpException;
                }
            }

            $method = $class->getMethod($method);
            $annotation = $reader->getMethodAnnotation($method, Permissions::class);
            if (!empty($annotation)) {
                if (!$user->hasAccess($annotation->permissions, $annotation->all)) {
                    throw new AccessDeniedHttpException;
                }
            }
        } catch (AccessDeniedHttpException $e) {
            if (Request::ajax()) {
                return Response::json(
                    [
                        'error' => $e->getMessage(),
                        'file'  => $e->getFile(),
                        'line'  => $e->getLine(),
                    ],
                    403,
                    [],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                );
            } else {
                return view(property_exists($this, 'denyView') ? $this->denyView : 'deny');
            }
        }

        return $method->invokeArgs($this, $parameters);
    }
}