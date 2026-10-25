<?php

declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    private int $page;
    private int $lastPage;
    private int $total;
    private int $perPage;

    public function __construct(int $total, int $perPage, int $page)
    {
        $this->total = max(0, $total);
        $this->perPage = max(1, $perPage);
        $this->lastPage = max(1, (int) ceil($this->total / $this->perPage));
        $this->page = min(max(1, $page), $this->lastPage);
    }

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    public function from(): int
    {
        return $this->total === 0 ? 0 : $this->offset() + 1;
    }

    public function to(): int
    {
        return min($this->total, $this->offset() + $this->perPage);
    }

    /** @return array<int, int|string> */
    public function pages(int $window = 2): array
    {
        $pages = [];
        $start = max(1, $this->page - $window);
        $end = min($this->lastPage, $this->page + $window);

        if ($start > 1) {
            $pages[] = 1;
            if ($start > 2) {
                $pages[] = '…';
            }
        }
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }
        if ($end < $this->lastPage) {
            if ($end < $this->lastPage - 1) {
                $pages[] = '…';
            }
            $pages[] = $this->lastPage;
        }
        return $pages;
    }

    public function url(int $page): string
    {
        $params = $_GET;
        $params['page'] = $page;
        $path = Request::capture()->path();
        return url($path) . '?' . http_build_query($params);
    }
}
