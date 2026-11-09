<?php

namespace PHPFramework;

class Pagination {
    protected int $countPages;
    protected int $currentPage;
    protected string $uri;
    protected int $totalRecords;
    protected int $perPage;
    protected int $midSize;
    protected int $maxPages;
    protected string $template;

    public function __construct(
        int $totalRecords = 1,
        ?int $perPage = null,
        ?int $midSize = null,
        ?int $maxPages = null,
        ?string $template = null
    ) {
        $this->totalRecords = $totalRecords;
        $this->perPage = $perPage ?? Config::$paginationSettings['perPage'];
        $this->midSize = $midSize ?? Config::$paginationSettings['midSize'];
        $this->maxPages = $maxPages ?? Config::$paginationSettings['maxPages'];
        $this->template = $template ?? Config::$paginationSettings['tpl'];
        $this->countPages = $this->getCountPages();
        $this->currentPage = $this->getCurrentPage();
        $this->uri = $this->getParams();
        $this->midSize = $this->getMidSize();
    }

    protected function getCountPages(): int {
        return (int) ceil($this->totalRecords / $this->perPage) ?: 1;
    }

    protected function getCurrentPage(): int {
        $page = request()->get('page', 1);

        if ($page < 1 or $page > $this->countPages) {
            abort();
        }

        return $page;
    }

    protected function getParams(): string {
        $url = parse_url(request()->uri);
        $uri = str_replace('/', '', $url['path']);

        if (!empty($url['query']) && $url['query'] !== '&') {
            parse_str($url['query'], $params);

            if (isset($params['page'])) {
                unset($params['page']);
            }

            if (!empty($params)) {
                $uri .= '?' . http_build_query($params);
            }
        }

        return $uri;
    }

    protected function getMidSize(): int {
        return ($this->countPages <= $this->maxPages) ? $this->countPages : $this->midSize;
    }

    public function getStart(): int {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function getPages(): string {
        $back = '';
        $forward = '';
        $startPage = '';
        $lastPage = '';
        $pagesLeft = [];
        $pagesRight = [];
        $currentPage = $this->currentPage;

        if ($this->currentPage > 1) {
            $back = $this->getLink($this->currentPage - 1);
        }

        if ($this->currentPage < $this->countPages) {
            $forward = $this->getLink($this->currentPage + 1);
        }

        if ($this->currentPage > $this->midSize + 1) {
            $startPage = $this->getLink(1);
        }

        if ($this->currentPage < ($this->countPages - $this->midSize)) {
            $lastPage = $this->getLink($this->countPages);
        }

        for ($index = $this->midSize; $index > 0; $index--) {
            if ($this->currentPage - $index > 0) {
                $pagesLeft[] = [
                    'link' => $this->getLink($this->currentPage - $index),
                    'number' => $this->currentPage - $index,
                ];
            }
        }

        for ($index = 1; $index <= $this->midSize; $index++) {
            if ($this->currentPage + $index <= $this->countPages) {
                $pagesRight[] = [
                    'link' => $this->getLink($this->currentPage + $index),
                    'number' => $this->currentPage + $index,
                ];
            }
        }

        return view()->renderPartial($this->template, compact(
            'back',
            'forward',
            'startPage',
            'lastPage',
            'pagesLeft',
            'pagesRight',
            'currentPage'
        ));
    }

    protected function getLink($page): string {
        if ($page == 1) {
            return rtrim($this->uri, '#?&');
        }

        if (str_contains($this->uri, '&') or str_contains($this->uri, '?')) {
            return "{$this->uri}&page={$page}";
        }

        return "{$this->uri}?page={$page}";
    }

    public function __toString(): string {
        return $this->getPages();
    }
}
