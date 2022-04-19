<?php

namespace App\Service;

use App\Entity\Request\GetRelationsRequest;
use App\Entity\Response\ErrorResponse;
use App\Entity\Response\RelationsResponse;
use App\Entity\Response\ServiceResponseInterface;

class RequestService
{
    /**
     * @var QueryService
     */
    private QueryService $queryService;

    private RelationService $relationService;

    /**
     * @param LinkService $linkService
     * @param QueryService $queryService
     */
    public function __construct(QueryService $queryService, RelationService $relationService)
    {
        $this->queryService = $queryService;
        $this->relationService = $relationService;
    }

    /**
     * @param GetRelationsRequest $request
     * @return ServiceResponseInterface
     */
    public function getRelations(GetRelationsRequest $request): ServiceResponseInterface
    {
        try {
            $storedQuery = $this->queryService->find($request->getQuery());
            if (is_null($storedQuery)) {
                throw new \RuntimeException('Yet unknown query "' . $request->getQuery()->getQuery() . '" [' . $request->getQuery()->getLanguage()->getCode() . ']', 500);
            }

            $totalFound = null;
            if ($request->isDomainFiltered()) {
                $relationsIds = $this->relationService->getRelationsIdsWithDomain($storedQuery, $request->getDomain(), $request->getLimit(), $request->getOffset(), $request->getWeightThreshold());
                if ((sizeof($relationsIds) > 0) and $request->isNeedTotalFound()) {
                    $totalFound = $this->relationService->getTotalRelationsWithDomain($storedQuery, $request->getDomain(), $request->getWeightThreshold());
                }
            } else {
                $relationsIds = $this->relationService->getRelationsIds($storedQuery, $request->getLimit(), $request->getOffset(), $request->getWeightThreshold());
                if ((sizeof($relationsIds) > 0) and $request->isNeedTotalFound()) {
                    $totalFound = $this->relationService->getTotalRelations($storedQuery, $request->getWeightThreshold());
                }
            }
            if (sizeof($relationsIds) > 0) {
                $relations = $this->queryService->findByIds($relationsIds);
                $response = new RelationsResponse($relations, $request->getDomain());
                if (!is_null($totalFound)) {
                    $response->withTotalSize($totalFound);
                }

                return $response;
            } else {
                return (new RelationsResponse([], $request->getDomain()))->withTotalSize(0);
            }
        } catch (\Throwable $e) {
            return new ErrorResponse($e->getCode(), $e->getMessage());
        }
    }
}
