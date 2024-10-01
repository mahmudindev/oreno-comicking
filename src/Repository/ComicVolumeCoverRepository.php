<?php

namespace App\Repository;

use App\Entity\ComicVolumeCover;
use App\Model\OrderByDto;
use App\Util\Href;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ComicVolumeCover>
 */
class ComicVolumeCoverRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComicVolumeCover::class);
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
            ->leftJoin('c.link', 'cl')->addSelect('cl')
            ->leftJoin('cl.website', 'clw')->addSelect('clw');

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
                case 'linkWebsiteHosts':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('clw.host = :linkWebsiteHost');
                        $query->setParameter('linkWebsiteHost', $val[0]);
                        break;
                    }
                    $query->andWhere('clw.host IN (:linkWebsiteHosts)');
                    $query->setParameter('linkWebsiteHosts', $val);
                    break;
                case 'linkRelativeReferences':
                    $c = \count($val);
                    if ($c < 1) break;

                    foreach ($val as $k => $v) {
                        switch ($v) {
                            case null:
                                $val[$k] = '';
                                break;
                            case '':
                                $val[$k] = null;
                                break;
                        }
                    }

                    if ($c == 1) {
                        $query->andWhere('cl.relativeReference = :linkRelativeReference');
                        $query->setParameter('linkRelativeReference', $val[0]);
                        break;
                    }
                    $query->andWhere('cl.relativeReference IN (:linkRelativeReferences)');
                    $query->setParameter('linkRelativeReferences', $val);
                    break;
                case 'linkHREFs':
                    $c = \count($val);
                    if ($c < 1) break;

                    $qExOr = $query->expr()->orX();
                    foreach ($val as $k => $v) {
                        $href = new Href($v);

                        $qExOr->add('clw.host = :linkHREFA' . $k . ' AND ' . 'cl.relativeReference = :linkHREFB' . $k);
                        $query->setParameter('linkHREFA' . $k, $href->getHost());
                        $query->setParameter('linkHREFB' . $k, $href->getRelativeReference() ?? '');
                    }
                    $query->andWhere($qExOr);
                    break;
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $val) {
                if (!($val instanceof OrderByDto)) continue;

                if ($key > 8) break;

                switch ($val->name) {
                    case 'volumeComicCode':
                        $val->name = 'cvc.code';
                        break;
                    case 'volumeNumber':
                        $val->name = 'cv.number';
                        break;
                    case 'linkWebsiteHost':
                        $val->name = 'clw.host';
                        break;
                    case 'linkRelativeReference':
                        $val->name = 'cl.relativeReference';
                        break;
                    case 'createdAt':
                    case 'updatedAt':
                    case 'ulid':
                    case 'hint':
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

                switch ($val->name) {
                    case 'c.hint':
                        $query->addOrderBy($val->name, $val->order);

                        if (isset($val->custom['prefer'])) {
                            $vname = 'hintPrefer' . $key;
                            $vvals = \explode('+', $val->custom['prefer']);
                            $vselc = '(CASE';
                            foreach ($vvals as $k => $v) {
                                $v = \str_replace(['_', '%'], '', $v);

                                $vselc .= ' WHEN c.hint LIKE :' . $vname . $k;
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
        $q02 = false;
        $q02Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('c.link', 'cl');
            $c = true;
        };
        $q03 = false;
        $q03Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('cl.website', 'clw');
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
                case 'linkWebsiteHosts':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q02Func($q02, $query);
                    $q03Func($q03, $query);

                    if ($c == 1) {
                        $query->andWhere('clw.host = :linkWebsiteHost');
                        $query->setParameter('linkWebsiteHost', $val[0]);
                        break;
                    }
                    $query->andWhere('clw.host IN (:linkWebsiteHosts)');
                    $query->setParameter('linkWebsiteHosts', $val);
                    break;
                case 'linkRelativeReferences':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q02Func($q02, $query);

                    foreach ($val as $k => $v) {
                        switch ($v) {
                            case null:
                                $val[$k] = '';
                                break;
                            case '':
                                $val[$k] = null;
                                break;
                        }
                    }

                    if ($c == 1) {
                        $query->andWhere('cl.relativeReference = :linkRelativeReference');
                        $query->setParameter('linkRelativeReference', $val[0]);
                        break;
                    }
                    $query->andWhere('cl.relativeReference IN (:linkRelativeReferences)');
                    $query->setParameter('linkRelativeReferences', $val);
                    break;
                case 'linkHREFs':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q02Func($q02, $query);
                    $q03Func($q03, $query);

                    $qExOr = $query->expr()->orX();
                    foreach ($val as $k => $v) {
                        $href = new Href($v);

                        $qExOr->add('clw.host = :linkHREFA' . $k . ' AND ' . 'cl.relativeReference = :linkHREFB' . $k);
                        $query->setParameter('linkHREFA' . $k, $href->getHost());
                        $query->setParameter('linkHREFB' . $k, $href->getRelativeReference() ?? '');
                    }
                    $query->andWhere($qExOr);
                    break;
            }
        }

        return $query->getQuery()->getSingleScalarResult();
    }
}
