<?php

namespace App\Http\Controllers;

use App\Entity\Domain;
use App\Entity\Query;
use App\Entity\Request\GetRelationsRequest;
use App\Entity\Response\ErrorResponse;
use App\Entity\Response\RelationsResponse;
use App\Http\Requests\LinksRequest;
use App\Service\RequestService;
use Illuminate\Http\JsonResponse;
use Nbsbbs\Common\Language\LanguageFactory;

class LinksController extends Controller
{
    /**
     * @var RequestService
     */
    protected RequestService $requestService;

    /**
     * @param RequestService $requestService
     */
    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
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
        $validated = $linksRequest->validated();

        $request = new GetRelationsRequest(new Query($validated['query'], LanguageFactory::createLanguage($validated['lang_code'])));
        $request->withLimitOffset($validated['limit'], $validated['offset']);
        $request->withWeightThreshold($validated['weightThreshold']);
        if (!empty($validated['domain'])) {
            $request->withDomain(new Domain($validated['domain']));
        }

        $response = $this->requestService->getRelations($request);
        if ($response instanceof ErrorResponse) {
            return $this->returnError($response->getErrorMessage(), $response->getErrorCode());
        } elseif ($response instanceof RelationsResponse) {
            return new JsonResponse([
                'status' => true,
                'domain' => ($response->isDomainFiltered()) ? $response->getDomain()->getDomainName() : null,
                'queries' => $this->convertQueriesToArray($response),
            ]);
        } else {
            throw new \RuntimeException('Unexpected response type');
        }
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
        return new JsonResponse(['status' => false, 'error' => $error], $code);
    }
}
