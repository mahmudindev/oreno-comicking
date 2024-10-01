<?php

namespace App\Repository;

use App\Entity\ComicChapter;
use App\Model\OrderByDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ComicChapter>
 */
class ComicChapterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComicChapter::class);
    }

    public function findByCustom(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.comic', 'cc')->addSelect('cc')
            ->leftJoin('c.thumbnail', 'ct')->addSelect('ct')
            ->leftJoin('c.volume', 'cv')->addSelect('cv');

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'comicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cc.code = :comicCode');
                        $query->setParameter('comicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cc.code IN (:comicCodes)');
                    $query->setParameter('comicCodes', $val);
                    break;
                case 'volumeNumbers':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cv.number = :volumeNumber');
                        $query->setParameter('volumeNumber', $val[0]);
                        break;
                    }
                    $query->andWhere('cv.number IN (:volumeNumbers)');
                    $query->setParameter('volumeNumbers', $val);
                    break;
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $val) {
                if (!($val instanceof OrderByDto)) continue;

                if ($key > 7) break;

                switch ($val->name) {
                    case 'comicCode':
                        $val->name = 'cc.code';
                        break;
                    case 'volumeNumber':
                        $val->name = 'cv.number';
                        break;
                    case 'createdAt':
                    case 'updatedAt':
                    case 'number':
                    case 'version':
                    case 'releasedAt':
                        $val->name = 'c.' . $val->name;
                        break;
                    default:
                        continue 2;
                }

                switch (\strtolower($val->order ?? '')) {
                    case 'a':
                    case 'asc':
                    case 'ascending':
                        $val->order = 'ASC';
                        break;
                    case 'd':
                    case 'desc':
                    case 'descending':
                        $val->order = 'DESC';
                        break;
                    default:
                        $val->order = null;
                }

                switch (\strtolower($val->nulls ?? '')) {
                    case 'f':
                    case 'first':
                        $val->nulls = 'DESC';
                        break;
                    case 'l':
                    case 'last':
                        $val->nulls = 'ASC';
                        break;
                    default:
                        $val->nulls = null;
                }

                if ($val->nulls) {
                    $vname = \str_replace('.', '', $val->name . $key);
                    $vselc = '(CASE WHEN ' . $val->name . ' IS NULL THEN 1 ELSE 0 END) AS HIDDEN ' . $vname;

                    $query->addSelect($vselc);
                    $query->addOrderBy($vname, $val->nulls);
                }

                $query->addOrderBy($val->name, $val->order);
            }
        } else {
            $query->orderBy('c.number');
            $query->orderBy('c.version');
        }

        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        return $query->setCacheable(true)->getQuery()->getResult();
    }

    public function countCustom(array $criteria = []): int
    {
        $query = $this->createQueryBuilder('c')
            ->select('count(c.id)');

        $q01 = false;
        $q01Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('c.comic', 'cc');
            $c = true;
        };
        $q03 = false;
        $q03Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('c.volume', 'cv');
            $c = true;
        };

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'comicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);

                    if ($c == 1) {
                        $query->andWhere('cc.code = :comicCode');
                        $query->setParameter('comicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cc.code IN (:comicCodes)');
                    $query->setParameter('comicCodes', $val);
                    break;
                case 'volumeNumbers':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q03Func($q03, $query);

                    if ($c == 1) {
                        $query->andWhere('cv.number = :volumeNumber');
                        $query->setParameter('volumeNumber', $val[0]);
                        break;
                    }
                    $query->andWhere('cv.number IN (:volumeNumbers)');
                    $query->setParameter('volumeNumbers', $val);
                    break;
            }
        }

        return $query->getQuery()->getSingleScalarResult();
    }
}
