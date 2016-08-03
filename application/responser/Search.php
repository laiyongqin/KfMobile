<?php
namespace app\responser;

/**
 * 搜索页面响应类
 * @package app\responser
 */
class Search extends Responser
{
    /**
     * 获取搜索页面的页面数据
     * @param array $extraData 额外参数
     * @return array 页面数据
     */
    public function index($extraData = [])
    {
        debug('begin');
        $doc = null;
        $initTime = 0;
        try {
            debug('initBegin');
            $doc = \phpQuery::newDocumentHTML($this->response['document']);
            debug('initEnd');
            $initTime = debug('initBegin', 'initEnd');
        } catch (\Exception $ex) {
            $this->handleError($ex);
        }
        $commonData = array_merge($this->getCommonData($doc), $extraData);
        $matches = [];

        // 搜索信息
        $keyword = '';
        if (preg_match('/^(.+)\s-\s/', trim(pq('title')->text()), $matches)) {
            $keyword = trim_strip($matches[1]);
        }
        $searchInfo = trim(pq('.thread1 > tr:first-child > td:last-child')->html());

        // 分页导航
        $currentPageNum = 1;
        $maxPageNum = 1;
        $pageParam = '';
        $urlParam = '';
        $pqPages = pq('.pages:first');
        if (preg_match('/-\s*(\d+)\s*-/', $pqPages->find('li > a[href="javascript:;"]')->text(), $matches)) {
            $currentPageNum = intval($matches[1]);
        }
        $pageUrl = $pqPages->find('li:last-child > a')->attr('href');
        if (preg_match('/page=(\d+)/', $pageUrl, $matches)) $maxPageNum = intval($matches[1]);
        if (preg_match('/search\.php\?(.+)/', $pageUrl, $matches)) {
            $pageParam = strip_tags($matches[1]);
            $pageParam = preg_replace('/(&?keyword=)[^&]+/i', '$1' . $keyword, $pageParam);
            $pageParam = preg_replace('/&?page=[^&]+/i', '', $pageParam);
            $page = intval(input('page/d', 0));
            $urlParam = $pageParam . ($page > 0 ? '&page=' . $page : '');
        }

        // 主题列表
        $threadList = [];
        foreach (pq('.thread1 > tr:gt(1)') as $item) {
            $pqItem = pq($item);
            if (!$pqItem->find('td:first-child > a[href^="read.php"]')->length) continue;

            // 主题链接
            $pqThreadLink = $pqItem->find('td:first-child > a');
            $tid = 0;
            $threadTitle = '';
            if (preg_match('/tid=(\d+)(?:.+keyword=([^&]+))?/i', $pqThreadLink->attr('href'), $matches)) {
                $tid = intval($matches[1]);
            }
            $threadTitle = trim($pqThreadLink->html());
            $threadTitle = preg_replace('/<font color="([^"]+)">/i', '<span style="color: $1">', $threadTitle);
            $threadTitle = preg_replace('/<\/font>/i', '</span>', $threadTitle);

            // 主题所属版块
            $threadForum = trim_strip($pqItem->find('td:nth-child(2)')->text());

            // 主题作者、发表时间
            $pqThreadInfoCell = $pqItem->find('td:last-child');
            $authorUid = '';
            $author = '';
            $publishTime = '';
            $pqAuthor = $pqThreadInfoCell->find('a');
            if (preg_match('/uid=(\d+)/i', $pqAuthor->attr('href'), $matches)) {
                $authorUid = intval($matches[1]);
            }
            $author = trim_strip($pqAuthor->text());
            $pqThreadInfoContents = $pqThreadInfoCell->contents();
            if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/i', $pqThreadInfoContents->eq($pqThreadInfoContents->length - 1), $matches)) {
                $publishTime = $matches[0];
            }

            $threadList[] = [
                'tid' => $tid,
                'threadTitle' => $threadTitle,
                'threadForum' => $threadForum,
                'authorUid' => $authorUid,
                'author' => $author,
                'publishTime' => $publishTime,
            ];
        }

        $data = [
            'keyword' => $keyword,
            'searchInfo' => $searchInfo,
            'currentPageNum' => $currentPageNum,
            'prevPageNum' => $currentPageNum > 1 ? $currentPageNum - 1 : 1,
            'nextPageNum' => $currentPageNum < $maxPageNum ? $currentPageNum + 1 : $maxPageNum,
            'maxPageNum' => $maxPageNum,
            'pageParam' => $pageParam,
            'urlParam' => $urlParam,
            'threadList' => $threadList,
        ];
        debug('end');
        trace('phpQuery解析用时：' . debug('begin', 'end') . 's' . '（初始化：' . $initTime . 's）');
        if (config('app_debug')) trace('响应数据：' . json_encode($data, JSON_UNESCAPED_UNICODE));
        return array_merge($commonData, $data);
    }
}