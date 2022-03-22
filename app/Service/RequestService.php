<?php

namespace App\Service;

use App\Entity\Request\GetRelationsRequest;
use App\Entity\Response\ErrorResponse;
use App\Entity\Response\RelationsResponse;
use App\Entity\Response\ServiceResponseInterface;

class RequestService
{
    /**
     * @var LinkService
     */
    private LinkService $linkService;

    /**
     * @var QueryService
     */
    private QueryService $queryService;

    /**
     * @param LinkService $linkService
     * @param QueryService $queryService
     */
    public function __construct(LinkService $linkService, QueryService $queryService)
    {
        $this->linkService = $linkService;
        $this->queryService = $queryService;
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
                $relationsIds = $this->linkService->getRelationsIdsWithDomain($storedQuery, $request->getDomain(), $request->getLimit(), $request->getOffset(), $request->getWeightThreshold());
                if ((sizeof($relationsIds) > 0) and $request->isNeedTotalFound()) {
                    $totalFound = $this->linkService->getTotalRelationsWithDomain($storedQuery, $request->getDomain(), $request->getWeightThreshold());
                }
            } else {
                $relationsIds = $this->linkService->getRelationsIds($storedQuery, $request->getLimit(), $request->getOffset(), $request->getWeightThreshold());
                if ((sizeof($relationsIds) > 0) and $request->isNeedTotalFound()) {
                    $totalFound = $this->linkService->getTotalRelations($storedQuery, $request->getWeightThreshold());
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
