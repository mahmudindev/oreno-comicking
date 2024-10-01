<?php

namespace App\Repository;

use App\Entity\Link;
use App\Model\OrderByDto;
use App\Util\Href;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Link>
 */
class LinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Link::class);
    }

    public function findByCustom(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = $this->createQueryBuilder('l')
            ->leftJoin('l.website', 'lw')->addSelect('lw');

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'websiteHosts':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('lw.host = :websiteHost');
                        $query->setParameter('websiteHost', $val[0]);
                        break;
                    }
                    $query->andWhere('lw.host IN (:websiteHosts)');
                    $query->setParameter('websiteHosts', $val);
                    break;
                case 'relativeReferences':
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
                        $query->andWhere('l.relativeReference = :relativeReference');
                        $query->setParameter('relativeReference', $val[0]);
                        break;
                    }
                    $query->andWhere('l.relativeReference IN (:relativeReferences)');
                    $query->setParameter('relativeReferences', $val);
                    break;
                case 'hrefs':
                    $c = \count($val);
                    if ($c < 1) break;

                    $qExOr = $query->expr()->orX();
                    foreach ($val as $k => $v) {
                        $href = new Href($v);

                        $qExOr->add('lw.host = :hrefA' . $k . ' AND ' . 'l.relativeReference = :hrefB' . $k);
                        $query->setParameter('hrefA' . $k, $href->getHost());
                        $query->setParameter('hrefB' . $k, $href->getRelativeReference() ?? '');
                    }
                    $query->andWhere($qExOr);
                    break;
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $val) {
                if (!($val instanceof OrderByDto)) continue;

                if ($key > 4) break;

                switch ($val->name) {
                    case 'websiteHost':
                        $val->name = 'lw.host';
                        break;
                    case 'createdAt':
                    case 'updatedAt':
                    case 'relativeReference':
                        $val->name = 'l.' . $val->name;
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
            $query->orderBy('lw.host');
            $query->orderBy('l.relativeReference');
        }

        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        return $query->setCacheable(true)->getQuery()->getResult();
    }

    public function countCustom(array $criteria = []): int
    {
        $query = $this->createQueryBuilder('l')
            ->select('count(l.id)');

        $q01 = false;
        $q01Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('l.website', 'lw');
            $c = true;
        };

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'websiteHosts':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);

                    if ($c == 1) {
                        $query->andWhere('lw.host = :websiteHost');
                        $query->setParameter('websiteHost', $val[0]);
                        break;
                    }
                    $query->andWhere('lw.host IN (:websiteHosts)');
                    $query->setParameter('websiteHosts', $val);
                    break;
                case 'relativeReferences':
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
                        $query->andWhere('l.relativeReference = :relativeReference');
                        $query->setParameter('relativeReference', $val[0]);
                        break;
                    }
                    $query->andWhere('l.relativeReference IN (:relativeReferences)');
                    $query->setParameter('relativeReferences', $val);
                    break;
                case 'hrefs':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);

                    $qExOr = $query->expr()->orX();
                    foreach ($val as $k => $v) {
                        $href = new Href($v);

                        $qExOr->add('lw.host = :hrefA' . $k . ' AND ' . 'l.relativeReference = :hrefB' . $k);
                        $query->setParameter('hrefA' . $k, $href->getHost());
                        $query->setParameter('hrefB' . $k, $href->getRelativeReference() ?? '');
                    }
                    $query->andWhere($qExOr);
                    break;
            }
        }

        return $query->getQuery()->getSingleScalarResult();
    }
}
