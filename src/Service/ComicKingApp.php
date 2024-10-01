<?php

namespace App\Service;

use App\Model\OrderByDto;
use App\Repository\ComicCoverRepository;
use App\Repository\ComicExternalRepository;
use App\Repository\ComicSynopsisRepository;
use App\Repository\ComicTitleRepository;
use App\Repository\LanguageRepository;

class ComicKingApp
{
    public function __construct(
        private readonly LanguageRepository $languageRepository,
        private readonly ComicTitleRepository $comicTitleRepository,
        private readonly ComicCoverRepository $comicCoverRepository,
        private readonly ComicSynopsisRepository $comicSynopsisRepository,
        private readonly ComicExternalRepository $comicExternalRepository
    ) {}

    public function getLanguages(
        ?int $limit = null
    ): array {
        return $this->languageRepository->findByCustom(
            [],
            [],
            $limit
        );
    }

    public function getComicTitles(
        string $comicCode,
        ?array $langs = [],
        ?int $limit = null
    ): ?array {
        if (!$langs) {
            $langs = ['en'];
        }

        return $this->comicTitleRepository->findByCustom(
            ['comicCodes' => [$comicCode]],
            [
                new OrderByDto('languageLang', custom: [
                    'prefer' => \implode('+', $langs)
                ]),
                new OrderByDto('isSynonym', nulls: 'last'),
                new OrderByDto('isLatinized', 'desc', 'last'),
                new OrderByDto('createdAt', 'desc')
            ],
            $limit
        );
    }

    public function getComicCovers(
        string $comicCode,
        ?array $hints = [],
        ?int $limit = null
    ): ?array {
        return $this->comicCoverRepository->findByCustom(
            ['comicCodes' => [$comicCode]],
            [
                new OrderByDto('hint', custom: [
                    'prefer' => \implode('+', $hints)
                ]),
                new OrderByDto('createdAt', 'desc')
            ],
            $limit
        );
    }

    public function getComicSynopses(
        string $comicCode,
        ?array $sources = [],
        ?array $langs = [],
        ?int $limit = null
    ): ?array {
        if (!$langs) {
            $langs = ['en'];
        }

        return $this->comicSynopsisRepository->findByCustom(
            ['comicCodes' => [$comicCode]],
            [
                new OrderByDto('source', custom: [
                    'prefer' => \implode('+', $sources)
                ]),
                new OrderByDto('languageLang', custom: [
                    'prefer' => \implode('+', $langs)
                ]),
                new OrderByDto('createdAt', 'desc')
            ],
            $limit
        );
    }

    public function getComicExternals(
        string $comicCode,
        ?int $limit = null
    ): ?array {
        return $this->comicExternalRepository->findByCustom(
            ['comicCodes' => [$comicCode]],
            [
                new OrderByDto('isOfficial', 'desc'),
                new OrderByDto('isCommunity', 'desc', 'first'),
                new OrderByDto('linkWebsiteName'),
                new OrderByDto('linkWebsiteHost'),
                new OrderByDto('linkRelativeReference')
            ],
            $limit
        );
    }

    public function getRecommendedLangs(
        array $curLangs,
        array $priLangs
    ): array {
        $recLangs = [];

        foreach ($priLangs as $lang) {
            if (!\in_array($lang, $curLangs)) {
                continue;
            }

            \array_push($recLangs, $lang);
        }

        foreach ($priLangs as $lang) {
            if (!\str_contains($lang, '-')) {
                continue;
            }

            $langs = [];

            $langc = '';
            foreach (\explode('-', $lang) as $langPart) {
                if ($langc) {
                    $langc .= '-';
                }

                $langc .= $langPart;

                if (!\in_array($langc, $recLangs) && \in_array($langc, $curLangs)) {
                    \array_push($langs, $langc);
                }
            }

            \array_push($recLangs, ...\array_reverse($langs));
        }

        return $recLangs;
    }

    public function getHREF(
        string $websiteHost,
        ?string $relativeReference = null
    ): string {
        $href = '';

        if ($websiteHost) {
            $href .= '//' . $websiteHost;
        }

        return $href . $relativeReference;
    }
}
