<?php

namespace Apiato\Core\Traits;


use Apiato\Core\Abstracts\Transformers\Transformer;
use Apiato\Core\Exceptions\InvalidTransformerException;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Spatie\Fractal\Facades\Fractal;

trait ResponseTrait
{


    protected array $metaData = [];

    /**
     * @throws InvalidTransformerException
     */
    public function transform(
        $data ,
        $transformerName = null ,
        array $includes = [] ,
        array $meta = [] ,
        $resourceKey = null ,
        $single = false
    ): array
    {


        // first, we need to create the transformer
        if ( $transformerName instanceof Transformer ) {
            // check, if we have provided a respective TRANSFORMER class
            $transformer = $transformerName;
        } else {
            // of if we just passed the classname
            $transformer = new $transformerName();
        }

        // now, finally check, if the class is really a TRANSFORMER
//        if ( !($transformer instanceof Transformer) ) {
//            throw new InvalidTransformerException();
//        }

        // add specific meta information to the response message
        $this->metaData = [
            'include' => $transformer->getAvailableIncludes() ,
            'custom'  => $meta ,
        ];

        // no resource key was set
        if ( !$resourceKey ) {
            // get the resource key from the model
            $obj = null;
            if ( $data instanceof AbstractPaginator ) {
                $obj = $data->getCollection()->first();
            } else if ( $data instanceof Collection ) {
                $obj = $data->first();
            } else {
                $obj = $data;
            }


            $resourceKey = 'data';

        }

        $fractal = Fractal::create($data , $transformer)->withResourceName($resourceKey)->addMeta($this->metaData);

        // read includes passed via query params in url
        $requestIncludes = $this->parseRequestedIncludes();

        // merge the requested includes with the one added by the transform() method itself
        $requestIncludes = array_unique(array_merge($includes , $requestIncludes));

        // and let fractal include everything
        $fractal->parseIncludes($requestIncludes);

        // Transform empty array to null
        $fractalData = $fractal->toArray();


        if ( Request::has('filter') && $requestFilters = Request::get('filter') ) {
            $fractalData['data'] = $this->filterResponse($fractalData['data'] , explode(';' , $requestFilters));
        }


        if ( Request::has('exclude') && $requestFilters = Request::get('exclude') ) {
            $fractalData['data'] = $this->filterResponseExclude($fractalData['data'] , explode(';' , $requestFilters));
        }


        if ( !blank($fractalData['data']) ) {
            $fractalData['data'] = $this->transformNull($single ? $fractalData['data'][0] : $fractalData['data']);
        }


        $fractalData['status'] = true;


        return $fractalData;
    }

    protected function parseRequestedIncludes(): array
    {
        return explode(',' , Request::get('include'));
    }

    // transform to null when array is empty recursively
    protected function transformNull($array)
    {
        foreach ( $array as $key => $value ) {
            if ( is_array($value) ) {
                $array[$key] = $this->transformNull($value);
            }
        }

        return empty($array) ? null : $array;
    }

    private function filterResponse(array $responseArray , array $filters): array
    {
        foreach ( $responseArray as $k => $v ) {
            if ( in_array($k , $filters , true) ) {
                // we have found our element - so continue with the next one
                continue;
            }

            if ( is_array($v) ) {
                // it is an array - so go one step deeper
                $v = $this->filterResponse($v , $filters);
                if ( empty($v) ) {
                    // it is an empty array - delete the key as well
                    unset($responseArray[$k]);
                } else {
                    $responseArray[$k] = $v;
                }
            } else {
                // check if the array is not in our filter-list
                if ( !in_array($k , $filters) ) {
                    unset($responseArray[$k]);
                }
            }
        }

        return $responseArray;
    }

    private function filterResponseExclude(array $responseArray , array $filters): array
    {
        foreach ( $responseArray as $k => $v ) {
            if ( in_array($k , $filters , true) ) {
                // we have found our element - so continue with the next one
                unset($responseArray[$k]);
            }

            if ( is_array($v) ) {
                // it is an array - so go one step deeper
                $v = $this->filterResponse($v , $filters);
                if ( empty($v) ) {
                    // it is an empty array - delete the key as well
                    unset($responseArray[$k]);
                } else {
                    $responseArray[$k] = $v;
                }
            } else {
                // check if the array is not in our filter-list
                if ( in_array($k , $filters) ) {
                    unset($responseArray[$k]);
                }
            }
        }

        return $responseArray;
    }

    public function withMeta($data): self
    {
        $this->metaData = $data;

        return $this;
    }

    public function json($message , $status = 200 , array $headers = [] , $options = 0): JsonResponse
    {
        return new JsonResponse($message , $status , $headers , $options);
    }

    public function created($message = [] , $status = 201 , array $headers = [] , $options = 0 , $alert = null): JsonResponse
    {
        $message = [
            ...$message ,
            'message' => $alert ?? 'Created Successfully!' ,
        ];


        return new JsonResponse($message , $status , $headers , $options);
    }


    public function updated($message = [] , $status = 200 , array $headers = [] , $options = 0 , $alert = null): JsonResponse
    {
        $message = [
            ...$message ,
            'message' => $alert ?? 'Updated Successfully!' ,
        ];


        return new JsonResponse($message , $status , $headers , $options);
    }

    public function deleted($alert = null): JsonResponse
    {

        return $this->accepted($alert ?? "Deleted Successfully.");
    }

    public function accepted($message = null , $status = 202 , array $headers = [] , $options = 0): JsonResponse
    {
        $data = [ 'status' => true , ];
        if ( !is_null($message) ) {
            $data['message'] = $message;
        }


        return new JsonResponse($data , $status , $headers , $options);
    }

    public function noContent($status = 204): JsonResponse
    {
        return new JsonResponse(null , $status);
    }
}
