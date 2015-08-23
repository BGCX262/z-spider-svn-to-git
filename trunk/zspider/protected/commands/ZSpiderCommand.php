<?php
// set the default timezone to use. Available since PHP 5.1
//date_default_timezone_set('UTC');
date_default_timezone_set('Asia/Shanghai');
function exceptionErrorHandler($errno, $errstr, $errfile, $errline )
{
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exceptionErrorHandler");
// 转换error => exception, 捕获异常

class ZSpiderCommand extends CConsoleCommand
{
    const PAGE_CONTENT_MIN_LEN = 10000;

    public function actionClearDataAll4BFS()
    {
        $sql = "drop table if exists bfs";
        Yii::app()->sqlite_db->createCommand($sql)->execute();
        printf("clear\n");
        $sql = "
            create table bfs (
                id integer primary key,
                url varchar(255) default '' unique,
                visited tinyint default 0 
            )
            ";
        Yii::app()->sqlite_db->createCommand($sql)->execute();
    }

    public function actionClearDataAll4ranks()
    {
        $sql = "drop table if exists ranks";
        Yii::app()->db->createCommand($sql)->execute();
        $sql = "
            CREATE TABLE `ranks` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `url` varchar(255) DEFAULT '',
              `title` varchar(255) DEFAULT '',
              `rank_best_sell` int(11) DEFAULT NULL,
              `info_best_sell` varchar(255) DEFAULT '',
              `info_ranks` varchar(4096) DEFAULT '',
              `isbn_10` varchar(255) DEFAULT '',
              `isbn_13` varchar(255) DEFAULT '',
              `main_image` varchar(255) NOT NULL DEFAULT '',
              `thumb_image` varchar(255) NOT NULL DEFAULT '',
              `utime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `idx_url` (`url`),
              KEY `idx_rank_best_sell` (`rank_best_sell`),
              KEY `idx_isbn_10` (`isbn_10`),
              KEY `idx_isbn_13` (`isbn_13`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
        ";
        Yii::app()->db->createCommand($sql)->execute();
    }
    public function actionAddColumnImages4ranks()
    {
        $sql = "alter table ranks add column main_image varchar(255) not null default '' after isbn_13";
        Yii::app()->db->createCommand($sql)->execute();
        $sql = "alter table ranks add column thumb_image varchar(255) not null default '' after main_image";
        Yii::app()->db->createCommand($sql)->execute();
    }
    public function actionClearDataAll4NMonitor()
    {
        $sql = "drop table if exists n_monitor";
        Yii::app()->db->createCommand($sql)->execute();
        $sql = "
            create table n_monitor (
                id int not null auto_increment,
                k varchar(255) not null default '',
                v varchar(255) not null default '',
                primary key (id),
                unique key idx_k (k)
            ) engine=innodb default charset=utf8
            ";
        Yii::app()->db->createCommand($sql)->execute();
    }
    public function actionClearDataAll4GroupISBN()
    {
        $sql = "drop table if exists ranks_group";
        Yii::app()->db->createCommand($sql)->execute();
        $sql = "
            create table ranks_group (
                rank_id int not null default '0',
                primary key (rank_id)
            ) engine=innodb default charset=utf8
            ";
        Yii::app()->db->createCommand($sql)->execute();
    }
    public function actionRankGroup()
    {
        $startTime = time();
        $this->actionClearDataAll4GroupISBN();
        $sql = "insert into ranks_group select t.id from (select id, isbn_13, isbn_10, title from ranks order by utime desc) t group by t.isbn_13, t.isbn_10, t.title";
        Yii::app()->db->createCommand($sql)->execute();
        $endTime = time();
        printf("更新完成:%.2f秒\n", $endTime - $startTime);
    }

    public function actionIndex()
    {
        if (true || $this->isEmptyBFS()) {
            $urlList = array();
            $urlList[] = 'http://www.amazon.com/s/ref=lp_283155_nr_n_2?rh=n%3A283155%2Cn%3A%211000%2Cn%3A3&bbn=1000&ie=UTF8&qid=1399278514&rnid=1000';
            foreach ($urlList as $url) {
                $this->pushbackUrl($url);
            }
        }
        for ($row = $this->getMinUnvisitedRow() ; !empty($row) ; $row = $this->getMinUnvisitedRow()) {
            $url = $row['url'];
            $id = $row['id'];
            $this->actionSearch($url);
            $this->markVisited($id);
        }
    }

    protected function markVisited($id)
    {
        $sql = "update bfs set visited = '1' where id = :id";
        Yii::app()->sqlite_db->createCommand($sql)->execute(array('id' => $id));
        Yii::app()->sqlite_db->setActive(false);
    }
    protected function pushbackUrl($url)
    {
        if (!$this->isUsedBfs($url)) {
            if (!$this->isKnowedUnusedBfs($url))
                printf("not used bfs:%s\n", $url);
            return;
        }
        $sql = "insert into bfs (url) values (:url)";
        try {
            Yii::app()->sqlite_db->createCommand($sql)->execute(array('url'=>$url));
            Yii::app()->sqlite_db->setActive(false);
        } catch (Exception $e) {
            $this->printException($e, sprintf("bfs.url:%s", $url));
        }
    }
    protected function isEmptyBFS()
    {
        $sql = "select * from bfs limit 1";
        $r = Yii::app()->sqlite_db->createCommand($sql)->queryRow();
        return empty($r);
    }
    protected function getMinUnvisitedRow()
    {
        $sql = "select * from bfs where visited = '0' order by id asc limit 1 ";
        $r = Yii::app()->sqlite_db->createCommand($sql)->queryRow();
        Yii::app()->sqlite_db->setActive(false);
        return $r;
    }

    protected function processPage($url, $pageContent)
    {
        $bookPage = $pageContent;
        list($rank_best_sell,
             $info_best_sell,
             $info_ranks,
             $title,
             $isbn_10,
             $isbn_13) = $this->getPageInfos($bookPage);

        if (empty($title) || empty($rank_best_sell)) {
            return false;
        }
        if (empty($isbn_13) && empty($isbn_10)) {
            printf("This has not isbn:\"%s\"\n", $url);
            return false;
        }
        list($main_image, $thumb_image) = $this->getImages($bookPage);
        $rank = Ranks::model()->find('url=:url', array('url'=>$url));
        if (empty($rank))
            $rank = new Ranks;
        $rank->url = $url;
        $rank->rank_best_sell = $rank_best_sell;
        $rank->info_best_sell = $info_best_sell;
        $rank->info_ranks = $info_ranks;
        $rank->title = $title;
        $rank->isbn_10 = $isbn_10;
        $rank->isbn_13 = $isbn_13;
        $rank->main_image = $main_image;
        $rank->thumb_image = $thumb_image;
        $rank->utime = date("Y-m-d H:i:s");
        try {
            $rank->save();
        } catch (Exception $e) {
            $this->printException($e);
        }
        return true;
    }

    protected function getPageInfos($bookPage)
    {
        preg_match('/\#([\d,]+) in Books.*\)/', $bookPage, $matches);
        $rank_best_sell = isset($matches[1]) ? $matches[1] : '';
        $rank_best_sell= str_replace(',', '', $rank_best_sell);
        $info_best_sell = isset($matches[0]) ? $matches[0] : '';
        preg_match("#<ul class=('|\")zg_hrsr('|\").*</ul>#Us", $bookPage, $matches);
        $info_ranks = isset($matches[0]) ? $matches[0] : '';
        preg_match("#id=('|\")(productTitle|btAsinTitle)('|\").*>([^<]+)\s*<#Us", $bookPage, $matches);
        // var_dump($matches);
        $title = isset($matches[4]) ? $matches[4] : '';
        preg_match("#ISBN-10:</b>\s*([\d-]+)<#Us", $bookPage, $matches);
        $isbn_10 = isset($matches[1]) ? $matches[1] : '';
        preg_match("#ISBN-13:</b>\s*([\d-]+)<#Us", $bookPage, $matches);
        $isbn_13 = isset($matches[1]) ? $matches[1] : '';
        // var_dump($rank_best_sell, $info_best_sell, $info_ranks, $title, $isbn_10, $isbn_13);
        return array($rank_best_sell, $info_best_sell, $info_ranks, $title, $isbn_10, $isbn_13);
    }

    protected function getImages($bookPage)
    {
        preg_match("#<div id=['\"](main-image-container|main-image-content)['\"].*>.*<img .*src=['\"]([\w/:\-\.,_%]+)['\"].*</div>#Us", $bookPage, $matches);
        // var_dump($matches);
        $main_image_1 = isset($matches[2]) ? $matches[2] : '';
        preg_match("#['\"](mainUrl|main)['\"]:\[?['\"]([\w/:\-\.,_%]*)['\"]#Us", $bookPage, $matches);
        // var_dump($matches);
        $main_image_2 = isset($matches[2]) ? $matches[2] : '';
        preg_match("#['\"](thumbUrl|thumb)['\"]:['\"]([\w/:\-\.,_%]*)['\"]#Us", $bookPage, $matches);
        $main_image_3 = isset($matches[2]) ? $matches[2] : '';
        // var_dump($matches);
        // var_dump($main_image_1, $main_image_2, $main_image_3);
        $main_image = empty($main_image_2) ? (empty($main_image_3) ? $main_image_1 : $main_image_3) : $main_image_2;
        $thumb_image = $this->thumbImage($main_image);
        return array($main_image, $thumb_image);
    }

    protected function thumbImage($main_image_url)
    {
        if (empty($main_image_url)) return '';
        preg_match("#[\w/:\-\.,%]+(\._([\w,_-]+)_\.)(\w)#s", $main_image_url, $matches);
        // var_dump($matches);
        $thumb_image_url = $main_image_url;
        if (!isset($matches[2])) {
            $thumb_image_url = str_replace('.jpg', '._SL100_.jpg', $main_image_url);
            $thumb_image_url = str_replace('.gif', '._SL100_.gif', $thumb_image_url);
        } else {
            $thumb_image_url = str_replace($matches[2], 'SL100', $main_image_url);
        }
        return $thumb_image_url;
    }

    protected function getFullUrl($url)
    {
        $res = '';
        if (strpos($url, 'http') === 0) {
            if (strpos($url, 'www.amazon.com/') !== false) {
                $res = $url;
            }
        } else if (strpos($url, '/') === 0) {
            $res = 'http://www.amazon.com'.$url;
        }
        return $res;
    }

    public function actionSearch($uri)
    {
        $url = $this->getFullUrl($uri);
        var_dump($url);
        if (empty($url)) {
            printf("uri(%d):%s\n", strlen($uri), $uri);
            return false;
        }
        if (!$this->isUsedBfs($url)) {
            if (!$this->isKnowedUnusedBfs($url))
                printf("not used bfs:%s\n", $url);
            $this->deleteRanks(null, $url);
            return false;
        }

        $pageContent = '';

        $success = false;
        $try = 0;
        while (!$success && $try<10) {
            try {
                $try++;
                sleep($try);
                // $pageContent = file_get_contents($url);
                $pageContent = self::getRemoter()->request($url, 'get', array(), '', 10, true);
                //$pageContent = 'href="'.$url.'" '.'href="'.$url.'" ';
                if (!empty($pageContent) && (strlen($pageContent) >= self::PAGE_CONTENT_MIN_LEN || strlen($pageContent) == 1))
                    $success = true;
                if (strlen($pageContent) < self::PAGE_CONTENT_MIN_LEN) {
                    printf("%d:被判断为rebot,等待重试%d\n", $try, strlen($pageContent));
                }
            } catch (Exception $ex) {
                echo $try."--".date(DATE_ATOM)."--\t".$ex->getMessage()."\n";
                if (strpos($ex->getMessage(), 'HTTP/1.1') !== false) {
                    $success = true;
                    return false;
                }
                // echo $try."--".date("Y-m-d H:i:s")."--\t".$ex->getMessage()."\n";
            }
        }
        if (!$success) {
            printf("重试失败退出\n");
            return false;
            // exit;
        }
        if (empty($pageContent)) {
            printf("内容为空返回\n");
            return false;
        }
        preg_match_all('/href=[\'\"](.*?)[\'\"]/', $pageContent, $matches);
        $nextUrlList = $matches[1];
        foreach ($nextUrlList as $uri) {
            $this->pushbackUrl($uri);
        }
        $success = $this->processPage($url, $pageContent);
        return $success;
    }

    protected function printException(Exception $e, $extraStr = '')
    {
        if (strpos($e->getMessage(), 'CDbCommand failed to execute the SQL statement: SQLSTATE[23000]') === 0) {
        } else {
            if (!empty($extraStr)) printf("extra:%s\n", $extraStr);
            printf("%s\n", $e->getMessage());
        }
    }

    protected function isUsedBfs($uri)
    {
        $url = $this->getFullUrl($uri);
        if (strpos($url, '/dp/') !== false
            || strpos($url, '/s/') !== false
            || strpos($url, '/b/') !== false
            || strpos($url, '/e/') !== false
            || strpos($url, '/product/') !== false
            || strpos($url, '/books/') !== false
            || strpos($url, '/new-releases/') !== false
            || strpos($url, '/author-rank/') !== false
            || strpos($url, 'www.amazon.com/s?rh=') !== false
            || strpos($url, 'page') !== false
            ) {
            if (!$this->isKnowedUnusedBfs($url)) {
                return true;
            }
        }
        return false;
    }
    protected function isKnowedUnusedBfs($uri)
    {
        $url = $this->getFullUrl($uri);
        if (empty($url)) return true;
        if (strpos($url, '/product-reviews/') !== false
            || strpos($url, '/offer-listing/') !== false
            || strpos($url, '/review/') !== false
            || strpos($url, '/reader/') !== false
            || strpos($url, '/voting/') !== false
            || strpos($url, '-ebook/') !== false
            || strpos($url, '/css/') !== false
            ) {
            return true;
        }
        return false;
    }
    public function actionDeleteUnusedBfs($redo = '', $drop = '')
    {
        if (!empty($redo)) {
            $sql = "insert into bfs select * from bfs_unused";
            Yii::app()->sqlite_db->createCommand($sql)->execute();
        }
        if (!empty($drop)) {
            $sql = "drop table if exists bfs_unused";
            Yii::app()->sqlite_db->createCommand($sql)->execute();
        }
        $sql = "create table if not exists bfs_unused (
                id integer primary key,
                url varchar(255) default '' unique,
                visited tinyint default 0 
            )
            ";
        Yii::app()->sqlite_db->createCommand($sql)->execute();
        Yii::app()->sqlite_db->setActive(false);
        $n = $this->getNmonitor(__FUNCTION__);
        $sql = "select * from bfs where id > :cur_id order by id asc limit 1";
        $insertSql = "insert into bfs_unused (id, url, visited) values (:id, :url, :visited)";
        $deleteSql = "delete from bfs where id = :id";
        $row = Yii::app()->sqlite_db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
        Yii::app()->sqlite_db->setActive(false);
        while (!empty($row)) {
            $id = $row['id'];
            $uri = $row['url'];
            $visited = $row['visited'];
            if (!$this->isUsedBfs($uri)) {
                if (!$this->isKnowedUnusedBfs($uri)) {
                    printf("%d %s:%s\n", $id, $visited, $this->getFullUrl($uri));
                }
                try {
                    Yii::app()->sqlite_db->createCommand($insertSql)->execute(array('id' => $id,
                                                                             'url' => $uri,
                                                                             'visited' => $visited,
                                                                             )
                                                                       );
                    Yii::app()->sqlite_db->setActive(false);
                } catch (Exception $e) {
                    $this->printException($e);
                }
                try {
                    Yii::app()->sqlite_db->createCommand($deleteSql)->execute(array('id' => $id));
                    Yii::app()->sqlite_db->setActive(false);
                } catch (Exception $e) {
                    $this->printException($e);
                }
            }
            $n = $id;
            $this->setNmonitor(__FUNCTION__, $n);
            $row = Yii::app()->sqlite_db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
            Yii::app()->sqlite_db->setActive(false);
        }

    }

    protected function getNmonitor($k)
    {
        $sql = "select * from n_monitor where k = :k";
        $row = Yii::app()->db->createCommand($sql)->queryRow(true, array('k' => $k));
        $v = 0;
        if (!empty($row)) {
            $v = $row['v'];
        }
        return $v;
    }

    protected function setNmonitor($k, $v)
    {
        $old_v = $this->getNmonitor($k);
        $sql = "update n_monitor set v = :v where k = :k";
        if ($old_v == 0) {
            $sql = "insert into n_monitor (k, v) values (:k, :v)";
        }
        Yii::app()->db->createCommand($sql)->execute(array('k' => $k, 'v' => $v));
    }

    public function actionCheckBackUsedBFS()
    {
        $n = $this->getNmonitor('actionCheckBackUsedBFS');
        $sql = "select * from bfs_unused where id > :cur_id order by id asc limit 1";
        $insertSql = "insert into bfs (id, url, visited) values (:id, :url, :visited)";
        $deleteSql = "delete from bfs_unused where id = :id";
        $row = Yii::app()->sqlite_db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
        Yii::app()->sqlite_db->setActive(false);
        while (!empty($row)) {
            $id = $row['id'];
            $uri = $row['url'];
            $visited = $row['visited'];
            if ($this->isUsedBfs($uri)) {
                printf("%d %s:%s\n", $id, $visited, $this->getFullUrl($uri));
                try {
                    Yii::app()->sqlite_db->createCommand($insertSql)->execute(array('id' => $id,
                                                                             'url' => $uri,
                                                                             'visited' => $visited,
                                                                             )
                                                                       );
                    Yii::app()->sqlite_db->setActive(false);
                } catch (Exception $e) {
                    $this->printException($e);
                }
                try {
                    Yii::app()->sqlite_db->createCommand($deleteSql)->execute(array('id' => $id));
                    Yii::app()->sqlite_db->setActive(false);
                } catch (Exception $e) {
                    $this->printException($e);
                }
            }
            $n = $id;
            $this->setNmonitor('actionCheckBackUsedBFS', $n);
            $row = Yii::app()->sqlite_db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
            Yii::app()->sqlite_db->setActive(false);
        }

    }

    public function actionCheckUsedBFS()
    {
        $n = 0;
        $sql = "select * from ranks where id > :cur_id order by id asc limit 1";
        $row = Yii::app()->db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
        while (!empty($row)) {
            $id = $row['id'];
            $url = $row['url'];
            if (!$this->isUsedBfs($url)) {
                printf("%d:%s\n", $id, $this->getFullUrl($url));
            }
            $n = $id;
            $row = Yii::app()->db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
        }
        printf("没有输出就是没有问题\n");
    }

    public function actionUpdateRanks()
    {
        $n = $this->getNmonitor(__FUNCTION__);
        $sql = "select id, url, rank_best_sell from ranks where main_image not like 'http%' order by rank_best_sell asc, id asc limit 1";
        $row = Yii::app()->db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
        while (!empty($row)) {
            $id = $row['id'];
            $url = $row['url'];
            $rank_best_sell = $row['rank_best_sell'];
            printf("id:%s;rank:%s\n", $id, $rank_best_sell);
            $success = $this->actionSearch($url);
            if ($success) {
                $updateOtherSql = "
                    update
                      ranks a inner join ranks b on (a.isbn_13 = b.isbn_13 and a.title = b.title and a.isbn_10 = b.isbn_10)
                    set b.main_image = a.main_image , b.thumb_image = a.thumb_image
                    where a.id = :cur_id and b.id != :cur_id and b.main_image not like 'http%'
                ";
                Yii::app()->db->createCommand($updateOtherSql)->execute(array('cur_id' => $id));
            } else {
                $success = $this->actionSearch($url);
                if (!$success) {
                    // 二次失败直接删除，此链接失效
                    $this->deleteRanks($id);
                }
            }
            $n = $id;
            $this->setNmonitor(__FUNCTION__, $n);
            $row = Yii::app()->db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
        }

    }

    public function actionTestSearch($uri)
    {
        $url = $this->getFullUrl($uri);
        var_dump($url);
        if (empty($url)) {
            printf("uri(%d):%s\n", strlen($uri), $uri);
            return;
        }
        $pageContent = '';

        $success = false;
        $try = 0;
        while (!$success && $try<10) {
            try {
                $try++;
                sleep($try);
                // $pageContent = file_get_contents($url);
                //$pageContent = file_get_contents('/home/dev/svn/zspider/zspider/yc.html');
                $pageContent = self::getRemoter()->request($url, 'get', array(), '', 10, true);
                file_put_contents(Yii::app()->basePath.'/../yc.html', $pageContent);
                //error_log($pageContent, 3, '/home/dev/svn/zspider/zspider/yc.html');
                //$pageContent = 'href="'.$url.'" '.'href="'.$url.'" ';
                if (!empty($pageContent) && (strlen($pageContent) >= self::PAGE_CONTENT_MIN_LEN || strlen($pageContent) == 1)) {
                    // var_dump($pageContent);
                    $success = true;
                }
                if (strlen($pageContent) < self::PAGE_CONTENT_MIN_LEN) {
                    printf("%d:被判断为rebot,等待重试%d\n", $try, strlen($pageContent));
                }
            } catch (Exception $ex) {
                echo $try."--".date(DATE_ATOM)."--\t".$ex->getMessage()."\n";
                if (strpos($ex->getMessage(), 'HTTP/1.1') !== false) {
                    $success = true;
                }
                // echo $try."--".date("Y-m-d H:i:s")."--\t".$ex->getMessage()."\n";
            }
        }
        if (!$success) {
            printf("重试失败退出\n");
            return;
            // exit;
        }
        if (empty($pageContent)) {
            printf("内容为空返回\n");
            return;
        }
        var_dump($this->getPageInfos($pageContent));
        var_dump($this->getImages($pageContent));
        if (strlen($pageContent) < self::PAGE_CONTENT_MIN_LEN) {
            printf("%s\n", $pageContent);
        }
        printf("cnt:%d\n", strlen($pageContent));
    }

    private static function getRemoter()
    {
        static $remoter;
        if (false == $remoter instanceof RequestDelegate)
        {
            $remoter = new RequestDelegate();
        }
        return $remoter;
    }
    public function actionMoveBfs()
    {
        $n = $this->getNmonitor(__FUNCTION__);
        $sql = "select * from bfs where id > :cur_id order by id asc limit 1";
        $row = Yii::app()->db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
        while (!empty($row)) {
            $id = $row['id'];
            $url = $row['url'];
            printf("id:%s\n", $id);
            if ($this->isUsedBfs($url)) {
                unset($row['id']);
                try {
                    Yii::app()->sqlite_db->createCommand()->insert('bfs', $row);
                    Yii::app()->sqlite_db->setActive(false);
                } catch (Exception $e) {
                    $this->printException($e);
                }
            }
            $n = $id;
            $this->setNmonitor(__FUNCTION__, $n);
            $row = Yii::app()->db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
        }

    }

    public function actionDeleteUnusedRanks()
    {
        $n = $this->getNmonitor(__FUNCTION__);
        $sql = "select id, url from ranks where id > :cur_id order by id asc limit 1";
        $row = Yii::app()->db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
        while (!empty($row)) {
            $id = $row['id'];
            $uri = $row['url'];
            if (!$this->isUsedBfs($uri)) {
                // if (!$this->isKnowedUnusedBfs($uri)) {
                    printf("%d %s\n", $id, $this->getFullUrl($uri));
                    // }
                try {
                    $this->deleteRanks($id);
                } catch (Exception $e) {
                    $this->printException($e);
                }
            }
            $n = $id;
            $this->setNmonitor(__FUNCTION__, $n);
            $row = Yii::app()->db->createCommand($sql)->queryRow(true, array('cur_id' => $n));
        }

    }

    protected function deleteRanks($id = null, $url = null)
    {
        if (empty($id) && empty($url)) return false;
        $sql = "create table if not exists ranks_deleted like ranks";
        Yii::app()->db->createCommand($sql)->execute();
        $where = ' where 1=1';
        $binds = array();
        if (!empty($id)) {$where .= " and id = :id";$binds['id'] = $id;}
        if (!empty($url)) {$where .= " and url = :url";$binds['url'] = $url;}
        $sql = "select * from ranks";
        $row = Yii::app()->db->createCommand($sql.$where)->queryRow(true, $binds);
        if (empty($row)) return false;
        $id = $row['id'];
        unset($row['id']);
        try {
            Yii::app()->db->createCommand()->insert('ranks_deleted', $row);
        } catch (Exception $e) {
            $this->printException($e);
        }
        $sql = "delete from ranks where id = :id";
        Yii::app()->db->createCommand($sql)->execute(array('id' => $id));
        return true;
    }
}
