<?php

namespace App\Http\Controllers;

use App\Entity\Domain;
use App\Entity\Query;
use App\Entity\Request\GetRelationsRequest;
use App\Entity\Response\ErrorResponse;
use App\Entity\Response\RelationsResponse;
use App\Entity\Response\ServiceResponseInterface;
use App\Http\Requests\LinksRequest;
use App\Service\RequestService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Nbsbbs\Common\Language\LanguageFactory;
use RuntimeException;

class LinksController extends Controller
{
    /**
     * @var RequestService
     */
    protected RequestService $requestService;

    /**
     * @var float
     */
    protected float $startStamp;

    /**
     * @param RequestService $requestService
     */
    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
        $this->startStamp = microtime(true);
    }

    /**
     * @param LinksRequest $linksRequest
     * @return JsonResponse
     * @queryParam query string required Query text
     * @queryParam lang_code string required Language code
     * @queryParam domain string Domain name (for filtration purpose)
     * @queryParam limit int Maximum allowed results count (for paging)
     * @queryParam offset int Offset (for paging)
     * @queryParam weightThreshold int Minimum allowed link weight (for filtration purpose)
     */
    public function show(LinksRequest $linksRequest): JsonResponse
    {
        $response = $this->getRelationResponse($linksRequest);
        if ($response instanceof ErrorResponse) {
            return $this->returnError($response->getErrorMessage(), $response->getErrorCode());
        } elseif ($response instanceof RelationsResponse) {
            return new JsonResponse([
                'status' => true,
                'domain' => ($response->isDomainFiltered()) ? $response->getDomain()->getDomainName() : null,
                'queries' => $this->convertQueriesToArray($response),
                'totalFound' => $response->totalSize() ?? null,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            throw new RuntimeException('Unexpected response type');
        }
    }

    /**
     * @param LinksRequest $linksRequest
     *
     * @return Application|Factory|View
     */
    public function showPreview(LinksRequest $linksRequest)
    {
        $response = $this->getRelationResponse($linksRequest);
        if ($response instanceof ErrorResponse) {
            return view('relations', [
                'status' => false,
                'error' => $response->getErrorMessage(),
                'code' => $response->getErrorCode(),
                'languages' => $this->createLanguages(),
                'elapsed' => $this->getElapsedTime(),
                'validated' => $linksRequest->validated(),
            ]);
        } elseif ($response instanceof RelationsResponse) {
            return view('relations', [
                'status' => true,
                'domain' => ($response->isDomainFiltered()) ? $response->getDomain()->getDomainName() : null,
                'queries' => $this->convertQueriesToArray($response),
                'totalFound' => $response->totalSize() ?? null,
                'validated' => $linksRequest->validated(),
                'languages' => $this->createLanguages(),
                'elapsed' => $this->getElapsedTime(),
            ]);
        } else {
            throw new RuntimeException('Unexpected response type');
        }
    }

    /**
     * @return string
     */
    protected function getElapsedTime(): string
    {
        return number_format(microtime(true) - $this->startStamp, 3);
    }

    /**
     * @return array
     */
    protected function createLanguages(): array
    {
        $result = [];
        foreach (LanguageFactory::allLanguages() as $language) {
            $result[$language->getCode()] = $language->getTitle();
        }

        return $result;
    }

    /**
     * @param LinksRequest $linksRequest
     * @return ServiceResponseInterface
     */
    protected function getRelationResponse(LinksRequest $linksRequest): ServiceResponseInterface
    {
        $validated = $linksRequest->validated();
        $request = new GetRelationsRequest(new Query($validated['query'], LanguageFactory::createLanguage($validated['lang_code'])));
        $request->withLimitOffset($validated['limit'], $validated['offset']);
        $request->withWeightThreshold($validated['weightThreshold']);
        if (!empty($validated['domain'])) {
            $request->withDomain(new Domain($validated['domain']));
        }

        return $this->requestService->getRelations($request);
    }

    /**
     * @param RelationsResponse $response
     * @return array
     */
    protected function convertQueriesToArray(RelationsResponse $response): array
    {
        $result = [];
        foreach ($response->getQueries() as $query) {
            $result[] = ['query' => $query->getQuery(), 'lang' => $query->getLanguage()->getCode()];
        }
        return $result;
    }

    /**
     * @param string $error
     * @param int $code
     * @return JsonResponse
     */
    protected function returnError(string $error, int $code): JsonResponse
    {
        return new JsonResponse(['status' => false, 'error' => $error], 500);
    }
}
