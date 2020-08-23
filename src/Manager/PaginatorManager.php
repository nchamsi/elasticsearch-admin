<?php

namespace App\Manager;

class PaginatorManager
{
    public function paginate($paginate): array
    {
        $slice = 2;

        $paginate['pages'] = ceil($paginate['total'] / $paginate['size']);

        if (false === $paginate['page'] || '' == $paginate['page'] || false === is_numeric($paginate['page'])) {
            $paginate['page'] = 1;
        }

        if ($paginate['pages'] < $paginate['page']) {
            $paginate['page'] = 1;
        }

        if (true === isset($paginate['array_slice']) && true === $paginate['array_slice']) {
            $paginate['rows'] = array_slice($paginate['rows'], ($paginate['size'] * $paginate['page']) - $paginate['size'], $paginate['size']);
        }

        $pagesSlice = [];
        $paginate['start_page'] = $paginate['page'] - $slice;
        if (0 >= $paginate['start_page']) {
            $paginate['start_page'] = 1;
        }
        $paginate['end_page'] = $paginate['page'] + $slice;
        if ($paginate['pages'] < $paginate['end_page']) {
            $paginate['end_page'] = $paginate['pages'];
        }
        for ($i=$paginate['start_page']; $i<=$paginate['end_page']; $i++) {
            $pagesSlice[] = $i;
        }
        $paginate['pages_slice'] = $pagesSlice;

        if (false === in_array(1, $paginate['pages_slice'])) {
            $paginate['first'] = 1;
        } else {
            $paginate['first'] = false;
        }

        if (false === in_array($paginate['pages'], $paginate['pages_slice'])) {
            $paginate['last'] = $paginate['pages'];
        } else {
            $paginate['last'] = false;
        }

        if (1 < $paginate['page']) {
            $paginate['previous'] = $paginate['page'] - 1;
        } else {
            $paginate['previous'] = false;
        }

        if ($paginate['pages'] > $paginate['page']) {
            $paginate['next'] = $paginate['page'] + 1;
        } else {
            $paginate['next'] = false;
        }

        return $paginate;
    }
}
