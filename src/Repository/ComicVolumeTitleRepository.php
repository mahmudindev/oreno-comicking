<?php

namespace App\Repository;

use App\Entity\ComicVolumeTitle;
use App\Model\OrderByDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ComicVolumeTitle>
 */
class ComicVolumeTitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComicVolumeTitle::class);
    }

    public function findByCustom(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.volume', 'cv')->addSelect('cv')
            ->leftJoin('cv.comic', 'cvc')->addSelect('cvc')
            ->leftJoin('c.language', 'cl')->addSelect('cl');

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'volumeComicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cvc.code = :volumeComicCode');
                        $query->setParameter('volumeComicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cvc.code IN (:volumeComicCodes)');
                    $query->setParameter('volumeComicCodes', $val);
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

                if ($key > 9) break;

                switch ($val->name) {
                    case 'volumeComicCode':
                        $val->name = 'cvc.code';
                        break;
                    case 'volumeNumber':
                        $val->name = 'cc.number';
                        break;
                    case 'languageLang':
                        $val->name = 'cl.lang';
                        break;
                    case 'createdAt':
                    case 'updatedAt':
                    case 'ulid':
                    case 'content':
                    case 'isSynonym':
                    case 'isLatinized':
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
                    case 'l':
                    case 'last':
                        $val->nulls = 'ASC';
                    case 'f':
                    case 'first':
                        $val->nulls = 'DESC';
                        break;
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

                switch ($val->name) {
                    case 'cl.lang':
                        $query->addOrderBy($val->name, $val->order);

                        if (isset($val->custom['prefer'])) {
                            $vname = 'languageLangPrefer' . $key;
                            $vvals = \explode('+', $val->custom['prefer']);
                            $vselc = '(CASE';
                            foreach ($vvals as $k => $v) {
                                $v = \str_replace(['_', '%'], '', $v);

                                $vselc .= ' WHEN cl.lang LIKE :' . $vname . $k;
                                $vselc .= ' THEN ' . (\count($vvals) - $k);
                                $query->setParameter($vname . $k, $v . '%');
                            }
                            $vselc .= ' ELSE 0 END) AS HIDDEN ' . $vname;

                            $query->addSelect($vselc);
                            $query->addOrderBy($vname, 'DESC');
                            break;
                        }

                        break;
                    default:
                        $query->addOrderBy($val->name, $val->order);
                }
            }
        } else {
            $query->orderBy('c.ulid');
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
            $q->leftJoin('c.volume', 'cv');
            $c = true;
        };
        $q011 = false;
        $q011Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('cv.comic', 'cvc');
            $c = true;
        };

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'volumeComicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);
                    $q011Func($q011, $query);

                    if ($c == 1) {
                        $query->andWhere('cvc.code = :volumeComicCode');
                        $query->setParameter('volumeComicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cvc.code IN (:volumeComicCodes)');
                    $query->setParameter('volumeComicCodes', $val);
                    break;
                case 'volumeNumbers':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);

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
