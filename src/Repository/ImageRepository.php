<?php

namespace App\Repository;

use App\Entity\Image;
use App\Model\OrderByDto;
use App\Util\Href;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Image>
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function findByCustom(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = $this->createQueryBuilder('i')
            ->leftJoin('i.link', 'il')->addSelect('il')
            ->leftJoin('il.website', 'ilw')->addSelect('ilw');

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'linkWebsiteHosts':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('ilw.host = :linkWebsiteHost');
                        $query->setParameter('linkWebsiteHost', $val[0]);
                        break;
                    }
                    $query->andWhere('ilw.host IN (:linkWebsiteHosts)');
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
                        $query->andWhere('il.relativeReference = :linkRelativeReference');
                        $query->setParameter('linkRelativeReference', $val[0]);
                        break;
                    }
                    $query->andWhere('il.relativeReference IN (:linkRelativeReferences)');
                    $query->setParameter('linkRelativeReferences', $val);
                    break;
                case 'linkHREFs':
                    $c = \count($val);
                    if ($c < 1) break;

                    $qExOr = $query->expr()->orX();
                    foreach ($val as $k => $v) {
                        $href = new Href($v);

                        $qExOr->add('ilw.host = :linkHREFA' . $k . ' AND ' . 'il.relativeReference = :linkHREFB' . $k);
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

                if ($key > 5) break;

                switch ($val->name) {
                    case 'linkWebsiteHost':
                        $val->name = 'ilw.host';
                        break;
                    case 'linkRelativeReference':
                        $val->name = 'il.relativeReference';
                        break;
                    case 'createdAt':
                    case 'updatedAt':
                    case 'ulid':
                        $val->name = 'i.' . $val->name;
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
            $query->orderBy('i.ulid');
        }

        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        return $query->setCacheable(true)->getQuery()->getResult();
    }

    public function countCustom(array $criteria = []): int
    {
        $query = $this->createQueryBuilder('i')
            ->select('count(i.id)');

        $q01 = false;
        $q01Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('i.link', 'il');
            $c = true;
        };
        $q02 = false;
        $q02Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('il.website', 'ilw');
            $c = true;
        };

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'linkWebsiteHosts':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);
                    $q02Func($q02, $query);

                    if ($c == 1) {
                        $query->andWhere('ilw.host = :linkWebsiteHost');
                        $query->setParameter('linkWebsiteHost', $val[0]);
                        break;
                    }
                    $query->andWhere('ilw.host IN (:linkWebsiteHosts)');
                    $query->setParameter('linkWebsiteHosts', $val);
                    break;
                case 'linkRelativeReferences':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);

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
                        $query->andWhere('il.relativeReference = :linkRelativeReference');
                        $query->setParameter('linkRelativeReference', $val[0]);
                        break;
                    }
                    $query->andWhere('il.relativeReference IN (:linkRelativeReferences)');
                    $query->setParameter('linkRelativeReferences', $val);
                    break;
                case 'linkHREFs':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);
                    $q02Func($q02, $query);

                    $qExOr = $query->expr()->orX();
                    foreach ($val as $k => $v) {
                        $href = new Href($v);

                        $qExOr->add('ilw.host = :linkHREFA' . $k . ' AND ' . 'il.relativeReference = :linkHREFB' . $k);
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
