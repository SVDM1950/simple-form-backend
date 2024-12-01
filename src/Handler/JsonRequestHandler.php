<?php

namespace App\Handler;

use App\Handler\Exception\InvalidJsonDataException;
use App\Routing\Interface\RequestHandler;
use App\Routing\Interface\RoutingHandler;
use Exception;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonRequestHandler implements RequestHandler
{
    public function __invoke(Request $request, Response $response, RoutingHandler $handler): Response
    {
        try {
            $data = \json_decode((string)$request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

            if (\is_array($data)) {
                $request->request->replace($data);
            }
        } catch (JsonException $exception) {
//            $this->logger()->error("Invalid JSON data: {$exception->getMessage()}");

//            return $response
//                ->setStatusCode(Response::HTTP_BAD_REQUEST)
//                ->setData([
//                    'type' => 'general',
//                    'errors' => ['Invalid JSON data']
//                ]);
            throw new InvalidJsonDataException([$exception->getMessage()], $exception->getCode(), $exception);
        } catch (Exception $exception) {
//            $this->logger()->error("Invalid JSON data: {$exception->getMessage()}");

//            return $response
//                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR)
//                ->setData([
//                    'type' => 'general',
//                    'errors' => ['Invalid JSON data']
//                ]);
            throw new InvalidJsonDataException([$exception->getMessage()], $exception->getCode(), $exception);
        }

        return $handler->handle($request, $response);
    }
}
