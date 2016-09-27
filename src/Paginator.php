<?php

namespace Wjs\Support;

class Paginator
{
    protected $url   = null;
    protected $query = [];
    protected $next_count = 5;
    protected $pre_count  = 5;

    protected $page;
    protected $pages;
    protected $start;
    protected $end;

    protected $is_paddable = true;

    public function __construct($page, $pages, $pre_count = 5, $next_count = 5)
    {
        $this->setPages($pages);
        $this->setPage($page);
        $this->setPreCount($pre_count);
        $this->setNextCount($next_count);
        $this->setup();
    }

    public function setPage($value)
    {
        $value = $this->parseToAboveOne($value);
        $this->page = $this->pages < $value ? $this->pages : $value;
        return $this;
    }

    public function setPages($value)
    {
        $this->pages = $this->parseToAboveOne($value);
        return $this;
    }

    public function setPreCount($value)
    {
        $this->pre_count = $this->parseToAboveOne($value);
        return $this;
    }

    public function setNextCount($value)
    {
        $this->next_count = $this->parseToAboveOne($value);
        return $this;
    }

    public function setUrl($value)
    {
        $this->url = (string) $value;
        return $this;
    }

    public function setQuery(array $value)
    {
        $this->query = $value;
        return $this;
    }

    public function setPaddable(boolean $value)
    {
        $this->is_paddable = $value;
        return $this;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function getFrameSize()
    {
        return $this->next_count + $this->pre_count + 1;
    }

    public function getHref($value)
    {
        $value = (int) $value;
        $query = $this->query;
        $href = $this->url;
        if (1 !== $value or ! empty($query) )
        {
            $query['page'] = $value;
            $href .= '?'.http_build_query($query);
        }
        return $href;
    }

    public function getHrefs()
    {
        $query = $this->query;
        for ($i = $this->start; $i <= $this->end; $i++)
        {
            $query['page'] = $i;
            yield $i => $this->url.'?'.http_build_query($query);
        }
    }

    public function getFirstHref()
    {
        return $this->getHref(1);
    }

    public function getLastHref()
    {
        return $this->getHref($this->pages);
    }

    public function isPaddable()
    {
        return $this->is_paddable && ($this->pages > $this->getFrameSize());
    }

    public function setup()
    {
        $start = $this->getStartMargin();
        $end   = $this->getEndMargin();
        $pad_count = $this->getFrameSize() - ($end - $start + 1);
        if ($this->isPaddable() && $pad_count > 0) {
            $start = 1 === $start ? 1 : $start - $pad_count;
            $end   = $this->pages === $end ? $end : $end + $pad_count;
        }
        $this->start = $start;
        $this->end   = $end;
        return $this;
    }

    protected function getStartMargin()
    {
        $target_page = $this->page - $this->pre_count;
        return 1 > $target_page ? 1 : $target_page;
    }

    protected function getEndMargin()
    {
        $target_page = $this->page + $this->next_count;
        return $this->pages < $target_page ? $this->pages : $target_page;
    }

    protected function parseToAboveOne($value)
    {
        $value = (int) $value;
        return (1 > $value) ? 1 : $value;
    }
}