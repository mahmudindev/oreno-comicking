<?php

namespace App\Repository;

use App\Entity\ComicChapterTitle;
use App\Model\OrderByDto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ComicChapterTitle>
 */
class ComicChapterTitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComicChapterTitle::class);
    }

    public function findByCustom(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.chapter', 'cc')->addSelect('cc')
            ->leftJoin('cc.comic', 'ccc')->addSelect('ccc')
            ->leftJoin('c.language', 'cl')->addSelect('cl');

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'chapterComicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('ccc.code = :chapterComicCode');
                        $query->setParameter('chapterComicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('ccc.code IN (:chapterComicCodes)');
                    $query->setParameter('chapterComicCodes', $val);
                    break;
                case 'chapterNumbers':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cc.number = :chapterNumber');
                        $query->setParameter('chapterNumber', $val[0]);
                        break;
                    }
                    $query->andWhere('cc.number IN (:chapterNumbers)');
                    $query->setParameter('chapterNumbers', $val);
                    break;
                case 'chapterVersions':
                    $c = \count($val);
                    if ($c < 1) break;

                    if ($c == 1) {
                        $query->andWhere('cc.version = :chapterVersion');
                        $query->setParameter('chapterVersion', $val[0]);
                        break;
                    }
                    $query->andWhere('cc.version IN (:chapterVersions)');
                    $query->setParameter('chapterVersions', $val);
                    break;
            }
        }

        if ($orderBy) {
            foreach ($orderBy as $key => $val) {
                if (!($val instanceof OrderByDto)) continue;

                if ($key > 10) break;

                switch ($val->name) {
                    case 'chapterComicCode':
                        $val->name = 'ccc.code';
                        break;
                    case 'chapterNumber':
                        $val->name = 'cc.number';
                        break;
                    case 'chapterVersion':
                        $val->name = 'cc.version';
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
            $q->leftJoin('c.chapter', 'cc');
            $c = true;
        };
        $q011 = false;
        $q011Func = function (bool &$c, QueryBuilder &$q): void {
            if ($c) return;
            $q->leftJoin('cc.comic', 'ccc');
            $c = true;
        };

        foreach ($criteria as $key => $val) {
            $val = \array_unique($val);

            switch ($key) {
                case 'chapterComicCodes':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);
                    $q011Func($q011, $query);

                    if ($c == 1) {
                        $query->andWhere('ccc.code = :chapterComicCode');
                        $query->setParameter('chapterComicCode', $val[0]);
                        break;
                    }
                    $query->andWhere('ccc.code IN (:chapterComicCodes)');
                    $query->setParameter('chapterComicCodes', $val);
                    break;
                case 'chapterNumbers':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);

                    if ($c == 1) {
                        $query->andWhere('cc.number = :chapterNumber');
                        $query->setParameter('chapterNumber', $val[0]);
                        break;
                    }
                    $query->andWhere('cc.number IN (:chapterNumbers)');
                    $query->setParameter('chapterNumbers', $val);
                    break;
                case 'chapterVersions':
                    $c = \count($val);
                    if ($c < 1) break;

                    $q01Func($q01, $query);

                    if ($c == 1) {
                        $query->andWhere('cc.version = :chapterVersion');
                        $query->setParameter('chapterVersion', $val[0]);
                        break;
                    }
                    $query->andWhere('cc.version IN (:chapterVersions)');
                    $query->setParameter('chapterVersions', $val);
                    break;
            }
        }

        return $query->getQuery()->getSingleScalarResult();
    }
}
