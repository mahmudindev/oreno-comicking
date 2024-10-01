<?php

namespace App\Repository;

use App\Entity\ComicAuthorNote;
use App\Model\OrderByDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ComicAuthorNote>
 */
class ComicAuthorNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComicAuthorNote::class);
    }

    public function findByCustom(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.author', 'ca')->addSelect('ca')
            ->leftJoin('ca.comic', 'cac')->addSelect('cac')
            ->leftJoin('ca.type', 'cat')->addSelect('cat')
            ->leftJoin('ca.person', 'cap')->addSelect('cap')
            ->leftJoin('c.language', 'cl')->addSelect('cl');

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'authorComicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cac.code = :authorComicCode');
                        $query->setParameter('authorComicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cac.code IN (:authorComicCodes)');
                    $query->setParameter('authorComicCodes', $val);
                    break;
                case 'authorTypeCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cat.code = :authorTypeCode');
                        $query->setParameter('authorTypeCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cat.code IN (:authorTypeCodes)');
                    $query->setParameter('authorTypeCodes', $val);
                    break;
                case 'authorPersonCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cap.code = :authorPersonCode');
                        $query->setParameter('authorPersonCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cap.code IN (:authorPersonCodes)');
                    $query->setParameter('authorPersonCodes', $val);
                    break;
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $val) {
                if (!($val instanceof OrderByDto)) continue;

                if ($key > 7) break;

                switch ($val->name) {
                    case 'authorComicCode':
                        $val->name = 'cac.code';
                        break;
                    case 'authorTypeCode':
                        $val->name = 'cat.code';
                        break;
                    case 'authorPersonCode':
                        $val->name = 'cap.code';
                        break;
                    case 'languageLang':
                        $val->name = 'cl.lang';
                        break;
                    case 'createdAt':
                    case 'updatedAt':
                    case 'ulid':
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
            $q->leftJoin('c.author', 'ca');
            $c = true;
        };
        $q011 = false;
        $q011Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('ca.comic', 'cac');
            $c = true;
        };
        $q012 = false;
        $q012Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('ca.type', 'cat');
            $c = true;
        };
        $q013 = false;
        $q013Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('ca.person', 'cap');
            $c = true;
        };

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'authorComicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);
                    $q011Func($q011, $query);

                    if ($c == 1) {
                        $query->andWhere('cac.code = :authorComicCode');
                        $query->setParameter('authorComicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cac.code IN (:authorComicCodes)');
                    $query->setParameter('authorComicCodes', $val);
                    break;
                case 'authorTypeCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);
                    $q012Func($q012, $query);

                    if ($c == 1) {
                        $query->andWhere('cat.code = :authorTypeCode');
                        $query->setParameter('authorTypeCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cat.code IN (:authorTypeCodes)');
                    $query->setParameter('authorTypeCodes', $val);
                    break;
                case 'authorPersonCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);
                    $q013Func($q013, $query);

                    if ($c == 1) {
                        $query->andWhere('cap.code = :authorPersonCode');
                        $query->setParameter('authorPersonCode', $val[0]);
                        break;
                    }
                    $query->andWhere('cap.code IN (:authorPersonCodes)');
                    $query->setParameter('authorPersonCodes', $val);
                    break;
            }
        }

        return $query->getQuery()->getSingleScalarResult();
    }
}
