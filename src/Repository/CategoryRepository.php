<?php

namespace App\Repository;

use App\Entity\Category;
use App\Model\OrderByDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findByCustom(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.type', 'ct')->addSelect('ct')
            ->leftJoin('c.parent', 'cp')->addSelect('cp')
            ->leftJoin('c.link', 'cl')->addSelect('cl')
            ->leftJoin('cl.website', 'clw')->addSelect('clw');

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'typeCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('ct.code = :typeCode');
                        $query->setParameter('typeCode', $val[0]);
                        break;
                    }
                    $query->andWhere('ct.code IN (:typeCodes)');
                    $query->setParameter('typeCodes', $val);
                    break;
                case 'parentCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cp.code = :parentCode');
                        $query->setParameter('parentCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cp.code IN (:parentCodes)');
                    $query->setParameter('parentCodes', $val);
                    break;
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $val) {
                if (!($val instanceof OrderByDto)) continue;

                if ($key > 8) break;

                switch ($val->name) {
                    case 'typeCode':
                        $val->name = 'ct.code';
                        break;
                    case 'parentCode':
                        $val->name = 'cp.code';
                        break;
                    case 'linkWebsiteHost':
                        $val->name = 'clw.host';
                        break;
                    case 'linkRelativeReference':
                        $val->name = 'cl.relativeReference';
                        break;
                    case 'createdAt':
                    case 'updatedAt':
                    case 'code':
                    case 'name':
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
            $query->orderBy('ct.code');
            $query->orderBy('c.code');
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
            $q->leftJoin('c.type', 'ct');
            $c = true;
        };
        $q02 = false;
        $q02Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('c.parent', 'cp');
            $c = true;
        };

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'typeCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);

                    if ($c == 1) {
                        $query->andWhere('ct.code = :typeCode');
                        $query->setParameter('typeCode', $val[0]);
                        break;
                    }
                    $query->andWhere('ct.code IN (:typeCodes)');
                    $query->setParameter('typeCodes', $val);
                    break;
                case 'parentCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q02Func($q02, $query);

                    if ($c == 1) {
                        $query->andWhere('cp.code = :parentCode');
                        $query->setParameter('parentCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cp.code IN (:parentCodes)');
                    $query->setParameter('parentCodes', $val);
                    break;
            }
        }

        return $query->getQuery()->getSingleScalarResult();
    }
}
