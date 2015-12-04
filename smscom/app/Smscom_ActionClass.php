<?php
// vim: foldmethod=marker
/**
 *  Smscom_ActionClass.php
 *
 *  @author     {$author}
 *  @package    Smscom
 *  @version    $Id: app.actionclass.php 323 2006-08-22 15:52:26Z fujimoto $
 */

// {{{ Smscom_ActionClass
/**
 *  action実行クラス
 *
 *  @author     {$author}
 *  @package    Smscom
 *  @access     public
 */
class Smscom_ActionClass extends Ethna_ActionClass
{
    var $_toppage_setting_obj;
    
    var $url_handler;
    
    var $uh;
    
    /** @var array flashメッセージ格納用配列（セッション保存用） */
    var $_flash = array();
    
    /** @var array flashメッセージ格納用配列 */
    var $_flash_now = array();
    
    /**
     *  Ethna_ActionClassのコンストラクタ
     *
     *  @access public
     *  @param  object  Ethna_Backend   $backend    backendオブジェクト

     */
    function Smscom_ActionClass(&$backend)
    {
        parent::Ethna_ActionClass($backend);
        
        $c =& Ethna_Controller::getInstance();
        $this->url_handler =& $c->getUrlHandler();
        $this->uh = $this->url_handler;
    }
    
    /**
     *  flashメッセージの処理

     *
     *  @access private
     */
    function _handleFlash()
    {
        // セッションにflashメッセージが含まれていたら復元する

        $prev_flash = $this->session->get('__flash');
        
        if (!empty($prev_flash) && is_array($prev_flash)) $this->_flash_now = $prev_flash;
    }
    
    /**
     *  flashメッセージをセット
     *
     *  @access public
     *  @param string  $name   flashメッセージのキー
     *  @param mixed   $value  flashメッセージに格納する内容
     *  @param boolean $now    trueの場合は現在のリクエスト中のみ有効
     */
    function setFlash($name, $value, $now = false)
    {
        if ($now) {
            $this->_flash_now[$name] = $value;
        } else {
            $this->_flash_now[$name] = $value;
            $this->_flash[$name] = $value;
            
            if (!$this->session->isStart(true)) {
                $this->session->start();
                //$this->backend->log(LOG_WARNING, 'セッションが開始されていない状態では、flashメッセージ機能は正常に動作しません');
            }
        }
    }
    
    /**
     *  flashメッセージを返す
     *
     *  @access public
     *  @param string  $name  flashメッセージのキー
     *  @return mixed  $nameで指定されたflashメッセージの内容
     */
    function getFlash($name)
    {
        return $this->_flash_now[$name];
    }
    
    /**
     * PC用のメルマガポイント加算処理

     *
     * @param object $acc
     */
    function _checkMailMagazineAccess($acc)
    {
        if (Mobile_Util::isMobile()) {
            return;
        }
        
        $add_point = false;
        $mmc_get = isset($_GET['mmc']) ? $_GET['mmc'] : null;
        $mmc_cookie = isset($_COOKIE['mmc']) ? $_COOKIE['mmc'] : null;
        
        if ($mmc_get) {
            // URLにmmcがセットされている
            if ($acc->isMember()) {
                // 会員なら即座にポイント加算

                $add_point = true;
            } else {
                // 非会員ならクッキーに書き込み
                setcookie("mmc", $mmc_get, 0, '/');
            }
        } else if ($mmc_cookie) {
            // クッキーにmmcがセットされている
            if ($acc->isMember()) {
                // 会員ならポイント加算し、クッキー削除
                $add_point = true;
                setcookie("mmc", '', time() - 3600, '/');
            }
        }
        
        if ($add_point) {
            // ポイント加算処理

            //echo 'MAIL_LOGINポイント加算処理';
            $db = $this->backend->getDB('');
            $db->db->autocommit(false);
            $db->begin();
            
            $user_id = $this->session->get('user_id');
            $result = $this->backend->getManager('Point')->addPointDataByAction($user_id, 'MAIL_LOGIN');
            if (Ethna::isError($result)) {
                $db->rollback();
            } else {
                $db->commit();
            }
        }
    }

    /**
     *  アクション実行前の認証処理を行う
     *
     *  @access public
     *  @return string  遷移名(nullなら正常終了, falseなら処理終了)
     */
    function authenticate()
    {
        // エラーレポートの設定

        /*
        error_reporting(
            E_ERROR |
            E_WARNING |
            E_PARSE |
            E_NOTICE |
            E_CORE_ERROR |
            E_CORE_WARNING |
            E_COMPILE_ERROR |
            E_COMPILE_WARNING |
            E_USER_ERROR |
            E_USER_WARNING |
            E_USER_NOTICE
            //E_STRICT |
            //E_RECOVERABLE_ERROR |
            //E_ALL
        );
        */
        
        // 認証とベースURLの設定

        $this->af->setApp('base_url', $this->config->get('base_url'));
        $this->af->setApp('base_ssl_url', $this->config->get('base_ssl_url'));
        if (empty($_SERVER['HTTPS'])) {
            $this->af->setApp('base_scheme_url', $this->config->get('base_url'));
        } else {
            $this->af->setApp('base_scheme_url', $this->config->get('base_ssl_url'));
        }
        // #25 【Bug ID: 94】 Start Luvina Modify
        $user_insight_tag_enable = $this->config->get('user_insight_tag_enable');
        $this->af->setApp('user_insight_tag_enable', $user_insight_tag_enable);
        // #25 【Bug ID: 94】 End Luvina Modify
        //$auth = new Smscom_Auth($this->backend);
        //$auth->exec();
        //#58 Start Luvina Modify
        $this->af->setApp('google_manager_id', $this->config->get('google_manager_id'));
        //#58 End Luvina Modify
        $acc = new Smscom_AccessControll($this->backend);
        
        /* 
         * $this->_checkMailMagazineAccess($acc)をコメントアウトし、

         * PC用のメルマガ対応も、mmAutoLogin()で行うよう修正
　       * 
         */
        // #12 Start Luvina Modify
        /* @var $infomationMng Smscom_InformationManager */
        $infomationMng = $this->backend->getManager('Information');
        $infolist = $infomationMng->getRandomInfomation();
        $this->af->setApp('informationRandom', $infolist);
        $this->af->setApp('totalInfoRandom', count($infolist));
        // #12 End Luvina Modify
        #495 Start Luvina Modify
        // Check deny access
        if ($acc->checkDenyAccess()) {
            header('Location: /?action_denyAccess=true');
            exit;
        }
        
        #495 End Luvina Modify
        // モバイルメルマガからの自動ログイン
        $acc->mmAutoLogin();    
    
        // 自動ログインにチャレンジ
        $acc->tryAutoLogin();
		
	    // #495 Start Luvina Modify
        // check case auto_login
        $userId = $this->session->get('user_id');
        /* @var $objMgrUserProfile Smscom_UserProfileManager */
        $objMgrUserProfile = $this->backend->getManager('UserProfile');
        $userProfile = $objMgrUserProfile->getObjectProp( 'UserProfile', array('id', 'deny_access_flg'), array('id' => $userId) );
        // deny_access_flg = 1 制限ON
        if($userProfile['deny_access_flg'] == 1) {
            if ($_COOKIE['deny_access_flg'] != 1) {
            	setcookie('deny_access_flg', 1, time() + 60 * 60 * 24 * 365, '/');
            }
            
            header('Location: /?action_denyAccess=true');
            exit;
        }
        // #495 End Luvina Modify
        
        // $5 Start Luvina Modify
        $this->checkUseSmartphone();
        $this->setUriPcAndSmartphone();
        // $5 Start Luvina Modify
        
        // flashメッセージ機能の処理（セッション開始後に実行）

        $this->_handleFlash();
        
        // PC用のメルマガからのアクセス判定処理

        // $this->_checkMailMagazineAccess($acc);
        
        // 管理ページに非管理者でアクセスした場合は管理ログイン画面へ遷移
        if ($acc->isAdminArea() && !$acc->isAdmin()) {
            return 'admin_login_index';
        }
        
        // 会員ページに非会員でアクセスした場合はログイン画面へ遷移
        //if ($acc->isMemberArea() && !$acc->isMember()) {
        //    return 'login_index';
        //}
        
        // 非会員は全てログイン画面へ飛ばす

        // #5 Start Luvina Modify
        setcookie('user_id', '', time() - 60*60*24*30 , '/');
        // #5 End Luvina Modify

        if (!$acc->isMember()) {
            return 'login_index';
        }
        
        if ($acc->isLogin()) {
            // base_url と base_ssl_url をセット
            if (!$acc->isAdminArea()) {
                $this->af->setApp('base_url', $this->config->get('base_url') . 'member/');
                $this->af->setApp('base_ssl_url', $this->config->get('base_ssl_url') . 'member/');
                if (empty($_SERVER['HTTPS'])) {
                    $this->af->setApp('base_scheme_url', $this->config->get('base_url') . 'member/');
                } else {
                    $this->af->setApp('base_scheme_url', $this->config->get('base_ssl_url') . 'member/');
                }
            } else {
                //$this->af->setApp('base_url', $this->config->get('base_url') . 'admin/');
                $this->af->setApp('base_url', $this->config->get('base_ssl_url') . 'admin/'); // 管理サイトは全てSSL通信にする
                $this->af->setApp('base_ssl_url', $this->config->get('base_ssl_url') . 'admin/');
                $this->af->setApp('base_scheme_url', $this->config->get('base_ssl_url') . 'admin/');
            }
            
            // 会員プロフィールはリクエスト毎に更新するように仕様変更
            
            $is_login = $this->session->get('is_login');
            $user_id = $this->session->get('user_id');
            $user_profile_obj = $this->backend->getObject('UserProfile', 'id', $user_id);
            if (!$user_profile_obj->isValid() || $user_profile_obj->get('display_type') == 0) {
                return 'login_index';
            }
            
            /*// セッションの中身はいちいち更新しない

            $this->session->set('authorization_type', $user_profile_obj->get('authorization_type');
            $this->session->set('user_name', $user_profile_obj->get('nickname');
            $this->session->set('user_image_file', $user_profile_obj->get('user_image_file');
            $this->session->set('total_point', $user_profile_obj->get('total_point');
            */
            
            $user = array();
            $user['is_login'] = $is_login;
            $user['user_id'] = $user_id;
            $user['authorization_type'] = $user_profile_obj->get('authorization_type');
            $user['user_name'] = $user_profile_obj->get('nickname');
            $user['nickname'] = $user_profile_obj->get('nickname');
            $user['user_image_file'] = $user_profile_obj->get('user_image_file');
            $user['total_point'] = $user_profile_obj->get('total_point');
            // #23 Start Luvina Modify
            $user['mail_address'] = $user_profile_obj->get('mail_address');
            // #23 End Luvina Modify
            // モバイルの場合はかんたんログイン設定の有無を追加
            if (Mobile_Util::isMobile()) {
                $user['is_set_easylogin'] = $this->session->get('is_set_easylogin');
            }
            //#58 Start Luvina Modify
            /* @var $userServiceMng Smscom_UserServiceManager  */
            $userServiceMng = $this->backend->getManager('UserService');
            $service_ids = $userServiceMng->getUserServiceIds($user_id);
            $googleManager = array();
            $googleManager['service_id'] = $service_ids;
            $googleManager['prefecture_id'] = $user_profile_obj->get('prefecture_id');
            $googleManager['office_sales_scale'] = $user_profile_obj->get('office_sales_scale');
            $googleManager['companies_care'] = $user_profile_obj->get('companies_care');
            $user['google_manager'] = $googleManager;
            //#58 End Luvina Modify
            $this->af->setApp('user', $user);

            // #5 Start Luvina Modify
            setcookie('user_id', $user_profile_obj->get('user_id'), time()+60*60*24*30 , '/');
            // #5 End Luvina Modify

            // #077 Start Luvina Modify
            $userProfileMgr = $this->backend->getManager('UserProfile');
            $check = $userProfileMgr->checkPublicAkContentByUserId($user_id);
            $this->af->setApp('checkPublicAkContent', $check);
            // #077 End Luvina Modify
            
        }
        
        // #12 Start Luvina Modify
        $user_id = $this->getUserId();
        // $4 Start Luvina Modify
        if($user_id > 0) {
            $arrConditionMessage = array();
            $arrConditionMessage['message_to'] = array('=', $user_id);
            $arrConditionMessage['delete_flg'] = array('=', 0);
            $arrConditionMessage['message_status'] = array('=', 2);
            $arrConditionMessage['to_recycle_flg'] = array('=', 0);

            $order = array(
                'message_receive_date' => OBJECT_SORT_DESC,
                'message_id' => OBJECT_SORT_DESC,
            );

            /* @var $message_heads_man Smscom_MessageHeadsManager */
            $message_heads_man = $this->backend->getManager('MessageHeads');
            $message_heads_prop_list = $message_heads_man->getMessageHeads(0, 0, array('id'), $arrConditionMessage, $order, 0, true);

            $message_count = $message_heads_prop_list['total'];
            $this->af->setApp('message_count', $message_count);
        }
        // $4 End Luvina Modify
        // #12 End Luvina Modify
        // #25 Start Luvina Modify
        // #25 【Bug ID: 94】 Start Luvina Modify
        // move code
        // #25 【Bug ID: 94】 End Luvina Modify
        if($user_insight_tag_enable) {
            if($user_id > 0) {
                $userProfileMgr = $this->backend->getManager('UserProfile');
                $userDataLocal = $userProfileMgr->getDataUserLocal($user_id);
                $this->af->setApp('uih_id', $user_id);
                // #25 【Bug ID: 81】 Start Luvina Modify
                $this->af->setApp('uih_service_ids', $userDataLocal['service_name']);
                // #25 【Bug ID: 81】 End Luvina Modify
                $this->af->setApp('uih_office_sales_scale', $userDataLocal['office_sales_scale']);
                $this->af->setApp('uih_companies_care', $userDataLocal['companies_care']);
            }
        }
        // #25 End Luvina Modify
        return parent::authenticate();
    }
    
    /**
     *  ログインしているかどうかのチェック
     *
     *  @access public
     *  @return boolean true:ログイン済み, false:未ログイン
     */
    function isLogin()
    {
        $user = $this->af->getApp('user');
        if ($user && $user['is_login']) {
            return true;
        }
        return false;
    }
    
    /**
     *  ユーザーIDの取得

     *
     *  @access public
     *  @return mixed ユーザーID:ログイン済み, null:未ログイン
     */
    function getUserId()
    {
        $user = $this->af->getApp('user');
        if ($user && $user['user_id']) {
            return $user['user_id'];
        }
        return null;
    }
    // #077 Start Luvina Modify
    /**
     * Count Number Unread Reply
     * 
     * @author  Luvina
     * @access  public
     *
     */
    function getNumberUnreadReply() {
        $user_id = $this->getUserId();
        
        $api_site = $this->backend->getConfig()->get('api_site');
        $postData = array();
        $postData['action_api_qa'] = true;
        $postData['type'] = '3003';
        $postData['temp'] = 1;
        $postData['user_id'] = $user_id;
        $postData['count'] = 1;
        
        /* @var $mgrApi Smscom_ApiManager */
        $mgrApi = $this->backend->getManager('Api');
        $resultApi = $mgrApi->apiCommon($api_site, $postData, CARETOMO_SITE_KEY);
        
        if (Ethna::isError($resultApi)) {
            return $resultApi;
        }
        if ($resultApi['message'] == "NG") {
            return new PEAR_Error('DBの登録でエラーがありました');
        }
        $this->af->setApp('countUnreadReply', $resultApi['count']);
    }
    // #077 End Luvina Modify
    /**
     *  アクション実行前の処理(フォーム値チェック等)を行う
     *
     *  @access public
     *  @return string  遷移名(nullなら正常終了, falseなら処理終了)
     */
    function prepare()
    {
        return parent::prepare();
    }

    /**
     *  アクション実行

     *
     *  @access public
     *  @return string  遷移名(nullなら遷移は行わない)
     */
    function perform()
    {
        return parent::perform();
    }
    
    // $5 Start Luvina Modify
    function checkUseSmartphone()
    {
        if(!Mobile_Util::isSmartPhone()) return;
        if(!$this->session->isStart()) {
            $this->session->start();
        }
        
        $use_smartphone_flg = 1;
        
        if(isset($_REQUEST['device_type'])) {
            if($_REQUEST['device_type'] == DEVICE_TYPE_SMARTPHONE) {
                //$this->session->set('use_smartphone_flg', true);
                $use_smartphone_flg = 1;
                setcookie('use_smartphone_flg', $use_smartphone_flg, time() + (86400* 365), '/');
            } else {
                //$this->session->set('use_smartphone_flg', false);
                $use_smartphone_flg = 0;
                setcookie('use_smartphone_flg', $use_smartphone_flg, time() + (86400* 365), '/');
            }
        } else if (!is_null($_COOKIE['use_smartphone_flg'])){
            $use_smartphone_flg = (int)$_COOKIE['use_smartphone_flg'];
            setcookie('use_smartphone_flg', $use_smartphone_flg, time() + (86400* 365), '/');
        }
        
        if($use_smartphone_flg) {
            /* @var $ctl Smscom_Controller_Base */
            $ctl =& Ethna_Controller::getInstance();
            $ctl->setUseSmartphone(true);
        }
    }
    
    function setUriPcAndSmartphone() {
        $aryPram = $_GET;
        
        if(isset($aryPram['device_type'])) {
            unset($aryPram['device_type']);
        }
        
        if(isset($aryPram['btn_id'])) {
            unset($aryPram['btn_id']);
        }
        
        if(is_array($aryPram) && count($aryPram) > 0) {
            foreach ($aryPram AS $key => $value) {
                if(preg_match('/^action_*/i', $key)) {
                    unset($aryPram [$key]);
                    break;
                }
            }
        }
        
        $curAction = $this->backend->controller->getCurrentActionName();
        if(!isset($aryPram['action_' . $curAction])) {
            $aryPram['action'] = $curAction;
        }
        
        $aryPram['device_type'] = DEVICE_TYPE_PC;
        $urlPc = $this->uh->getUrlFor($aryPram);
        $aryPram['device_type'] = DEVICE_TYPE_SMARTPHONE;
        $urlSmartphone = $this->uh->getUrlFor($aryPram);
        
        $this->af->setApp('baseUriPc', $urlPc);
        $this->af->setApp('baseUriSmartPhone', $urlSmartphone);
        $this->af->setApp('isSmartphone', Mobile_Util::isSmartPhone());
    }
    // $5 End Luvina Modify
    // #14 Start Luvina Modify

    /**
     * _articleUploadFile
     * 
     * @param string $uploadMode
     * @param unknown_type $uploadFile
     * @param unknown_type $uploadImage
     * @param string $actionReturn
     */
    function _articleUploadFile($uploadMode, $uploadFile, $uploadImage, $actionReturn) {
        ini_set("memory_limit","-1");
        $authorId = $articleId = 0;
        $fileArticleName = $this->af->get('file_article_name');

        // ファイルの存在チェック
        $maxSizeImage = $this->config->get('file_image_upload_limit_maxsize');
        if((int)$maxSizeImage < 1) {
            $maxSizeImage = 2;
        }
        $maxSizeImage = $maxSizeImage * 1024 * 1024;

        $maxSizeArticle = $this->config->get('file_article_upload_limit_maxsize');
        if((int)$maxSizeArticle < 1) {
            $maxSizeArticle = 2;
        }
        $maxSizeArticle = $maxSizeArticle * 1024 * 1024;

        // ファイルの存在チェック
        if ($uploadFile['size'] > $maxSizeArticle ||
           (!file_exists($uploadFile['tmp_name']) && !empty($uploadFile['name']))) {
            $this->ae->add('upload_image', "{form}には、" . $maxSizeArticle . 'B以下のファイルを指定してください。');
        } elseif (!file_exists($uploadFile['tmp_name']) && empty($fileArticleName)) {
            $this->ae->add('upload_article', "{form}のアップロードに失敗しました。");
        } elseif ($uploadFile['type'] == 'text/plain') {
            // validate file name
            $checkValid = $this->_validateFileNameArticle($uploadMode, $uploadFile['name']);

            // read article from article file
            if($checkValid) {
                $this->_readArticleFromFileUpload($uploadFile);
            }
        } elseif(empty($fileArticleName)) {
            $this->ae->add('upload_article', '{form}がTXT形式ではありません');
        }

        // 画像形式をチェック
        $checkValidImage = true;
        if(isset($uploadImage['name']) && !empty($uploadImage['name'])) {
            $imageType = getimagesize($uploadImage['tmp_name']);
            if ($uploadImage['size'] > $maxSizeImage || !file_exists($uploadImage['tmp_name'])) {
                $checkValidImage = false;
                $this->ae->add('upload_image', "{form}には、" . $maxSizeImage . 'B以下のファイルを指定してください。');
            // ファイルの存在チェック
            } elseif (!file_exists($uploadImage['tmp_name'])) {
                $checkValidImage = false;
                $this->ae->add('upload_image', "{form}のアップロードに失敗しました。");
            } elseif ($imageType[2] != IMAGETYPE_JPEG && $imageType[2] != IMAGETYPE_PNG) {
                $checkValidImage = false;
                $this->ae->add('upload_image', '{form}がJPEG,JPG,PNG形式ではありません');
            }
        }

        // upload image
        if($checkValidImage && file_exists($uploadImage['tmp_name'])) {
            $document_root = $this->backend->getConfig()->get('document_root');
            $base_path = $this->backend->getConfig()->get('article_image_path');
            $base_dir = $this->backend->getConfig()->get('article_image_dir');
            if(!is_dir($base_dir)) {
                mkdir($base_dir);
            }

            $imageType = getimagesize($uploadImage['tmp_name']);
            $file_ext = "";
            if ($imageType[2] == IMAGETYPE_JPEG ) {
                $file_ext = "jpg";
            }elseif ($imageType[2] == IMAGETYPE_GIF ) {
                $file_ext = "gif";
            }elseif ($imageType[2] == IMAGETYPE_PNG ) {
                $file_ext = "png";
            }

            $authorId = $this->af->get('author_id');
            $articleId = $this->af->get('article_id');
            if(!$articleId) {
                /* @var $articleMng Smscom_ArticleManager */
                $articleMng = $this->backend->getManager('Article');

                $articleId = $articleMng->getNextID();
                if(Ethna::isError($articleId)) {
                    $this->ae->add('', "記事IDが正しくありません。");
                    return $actionReturn;
                }
            }

            $uniqId = $authorId . '_'. $articleId;
            $fname = md5($uniqId) . '.' . $file_ext;

            // 保管用ディレクトリパスの取得
            // ファイル名の文字列の先頭4文字を使って2階層深く掘る(１つのディレクトリに大量のファイルが貯まるのを防ぐため）
            $dir_path = Smscom_Util::getDepthDir($base_dir, $fname);
            $dir_path_view = Smscom_Util::getDepthDir($base_path, $fname);

            // 保管用ディレクトリの作成
            $dir_full_path = $dir_path;
            $result = Ethna_Util::mkdir($dir_full_path, 0775);
            if ($result == false && is_dir($dir_full_path) == false) {
                $this->ae->add('', "アップロードしたファイルのキャッシュディレクトリの作成に失敗しました");
                return $actionReturn;
            }

            // アップロードされたイメージファイルを保存するファイルパス名
            $filePath = Smscom_Util::joinPath($dir_path, $fname);
            $filePathView = Smscom_Util::joinPath($dir_path_view, $fname);

            if (!move_uploaded_file($uploadImage['tmp_name'], $filePath)) {
                $this->ae->add('', "アップロードしたファイルのリサイズ処理に失敗しました");
                return $actionReturn;
            }

            $this->af->set('image_name', $uploadFile['name']);
            $this->af->set('file_image_size', $uploadFile['size']);
            $this->af->set('image', $filePathView);
        }
    }

    /**
     * Read contents from file update
     * 
     * @param unknow_type $uploadFile
     * @return void
     */
    function _readArticleFromFileUpload($uploadFile) {
        // Read file and validate file content
        $fp = fopen($uploadFile['tmp_name'], 'rb');
        $lineIndex = 0;
        $articleBody = '';
        $aryContent = array();
        $isContentPage = false;
        $pageNumber = 1;
        while ( ($lineContent = fgets($fp)) !== false) {
            $lineIndex++;
            /* "auto" is expanded to "ASCII,JIS,UTF-8,EUC-JP,SJIS" */
            $lineContent = mb_convert_encoding($lineContent, "UTF-8", "auto");

            if($lineIndex >= 4 && preg_match("'<CONTENT_PAGE>'si", $lineContent, $match)) {
                $isContentPage = true;
                $pageNumber = $pageNumber+1;
                continue;
            }

            if($lineIndex >= 4 && $pageNumber){
                $aryContent[$pageNumber] .= $lineContent;
            }

            // validate keywords
            if($lineIndex === 1 && preg_match("'<keyword>(.*?)</keyword>'si", $lineContent, $match)) {
                if(empty($match[1])) {
                    $this->ae->add('keywords', '{form}が入力されていません。');
                } else {
                    $this->af->set('keywords', trim($match[1], "\t\n\r\0\x0B"));
                }
                continue;
            } elseif($lineIndex === 1) {
                $this->ae->add('keywords', '{form}が入力されていません。');
                continue;
            }

            // validate title
            if($lineIndex === 2 && preg_match("'<h1>(.*?)</h1>'si", $lineContent, $match)) {
                if(empty($match[1])) {
                    $this->ae->add('title', '{form}が入力されていません。');
                } else {
                    $this->af->set('title', $match[1]);
                }
                continue;
            } elseif($lineIndex === 2) {
                $this->ae->add('title', '{form}が入力されていません。');
                continue;
            }

            // validate lead words
            if($lineIndex === 3 && preg_match("'<h2>(.*?)</h2>'si", $lineContent, $match)) {
                if(empty($match[1])) {
                    $this->ae->add('lead', '{form}が入力されていません。');
                } else {
                    $this->af->set('lead', $match[1]);
                }
                continue;
            } elseif($lineIndex === 3) {
                $this->ae->add('lead', '{form}が入力されていません。');
                continue;
            }
        }

        if(!count($aryContent)) {
            $this->ae->add('content_article', '{form}が入力されていません。');
        } else {
            $this->af->set('content_article', $aryContent);
            $this->af->set('total_page', $pageNumber);
        }
    }

    /**
     * Validate file name of the article file
     * 
     * @param string $uploadMode
     * @param string $fileName
     * @return boolean $checkValid
     */
    function _validateFileNameArticle($uploadMode, $fileName) {
        $checkValid = true;
        // validate file name
        if(preg_match("/^(au[0-9]{1,11})\_(ar[0-9]{1,11})\_([0-9]{4})\-([0-9]{2})\-([0-9]{2})\.txt$/", $fileName, $matches)) {
            $articleId = $this->af->get('article_id');
            $articleNameOld = $currentAticle = $matches[1] . '_' . $matches[2];
            $authorId = str_replace('au', '', $matches[1]);

            // validate date published on web
            if(!checkdate($matches[4], $matches[5], $matches[3])) {
                $checkValid = false;
                $this->ae->add('', 'ウェブサイト上の公開日が正しくありません。');
            }

            if($uploadMode != 'add'){
                $articleDetailObj = $this->backend->getObject('Article', array('id', 'author_id', 'delete_flg'), array($articleId, $authorId, 0));
                if (!($articleDetailObj->isValid())) {
                    $checkValid = false;
                    $this->ae->add('', '記事IDが正しくありません。');
                } else {
                    $articleFileName = $articleDetailObj->get('file_article_name');
                    $aryArticleName =  preg_split("/_/", $articleFileName);
                    $articleNameOld = $aryArticleName[0] . '_' . $aryArticleName[1];
                }

                if($articleNameOld != $currentAticle) {
                    $checkValid = false;
                    $this->ae->add('', 'ファイル名の形式が正しくありません。ファイル名の形式がau1_ar2_2013-11-01.txtとなっています。');
                }
            }

        } else{
            $checkValid = false;
            $this->ae->add('', 'ファイル名の形式が正しくありません。ファイル名の形式がau1_ar2_2013-11-01.txtとなっています。');
        }

        if($authorId > 0) {
            $authorDetailObj = $this->backend->getObject('Author', array('id', 'delete_flg'), array($authorId, 0));
            if (!($authorDetailObj->isValid())) {
                $checkValid = false;
                $this->ae->add('', 'オーサーID が正しくありません。');
            } else {
                $this->af->set('author_id', $authorDetailObj->get('id'));
                $this->af->set('name', $authorDetailObj->get('name'));
            }
        }

        /* @var $articleMng Smscom_ArticleManager */
        $articleMng = $this->backend->getManager('Article');
        $total = $articleMng->checkArticleFileExist($fileName, $articleId);
        if($total) {
            $checkValid = false;
            $this->ae->add('', '記事ファイル名がすでに存在している。');
        }

         if($checkValid) {
             // #37 Start Luvina Modify
            $this->af->set('file_article_name', $matches[0]);
            $this->af->set('date_published', date('Y-m-d 07:00:00', strtotime($matches[3] .'-' . $matches[4] .'-' . $matches[5])));
             // #37 End Luvina Modify
        }

        return $checkValid;
    }

    /**
     * @author Luvina
     * Get ranking word in right menu
     */
    function getRankingKeywordMenuRight() {
        /* @var $articleKeywordMgr Smscom_ArticleKeywordManager */
        $articleKeywordMgr = $this->backend->getManager('ArticleKeyword');

        $limit = $this->config->get('menu_right_limit_keyword_ranking');
        $limitRankingWord = (is_numeric($limit) && $limit > 0) ? (int)$limit : 5;

        $dateLimitRanking = $this->config->get('carezine_date_limit_ranking');
        $dateLimitRanking  = ($dateLimitRanking < 1) ? $dateLimitRanking  : 7;
        $this->af->setApp('dateLimitRanking', $dateLimitRanking);

        $aryRankWord = $articleKeywordMgr->getListArticleKeyword($limitRankingWord, $dateLimitRanking);

        if (Ethna::isError($aryRankWord)) {
            $this->ae->add(null, 'DBの登録でエラーがありました', null);
            $aryRankWord = array();
        }

        $this->af->setApp('listRankingKeywordSideRight', $aryRankWord);
    }

    /**
     * @author Luvina
     * get list ranking view
     */
    function getRankingArticleMenuRight() {
        // setting the value to display new icon
        $this->setDateShowIconNew();

        // Get article new term
        $dateLimitRanking = $this->config->get('carezine_date_limit_ranking');
        $dateLimitRanking = ($dateLimitRanking < 1) ? 7 : $dateLimitRanking;

        /* ------ Get list ranking aricle note/人気記事ランキング ※直近7日間を集計 ----------*/
        /* @var $articleAccess Smscom_CarezinesAccessManager */
        $articleAccess = $this->backend->getManager('CarezinesAccess');
        $limit = $this->config->get('menu_right_limit_article_ranking');
        $limitRankingArticle = (is_numeric($limit) && $limit > 0) ? (int)$limit : 5;

        $aryListAccess = $articleAccess->getListRankingViewArticle($limitRankingArticle, 0, $dateLimitRanking);
        if (Ethna::isError($aryListAccess)) {
            $this->ae->add(null, 'DBの登録でエラーがありました', null);
        }

        $this->af->setApp('listRankingAccessSideRight', $aryListAccess['list']);
        $this->af->setApp('isRankingArticle', 1);
    }

    /**
     * @author Luvina
     * Get new list article in right menu
     */
    function getArticleMenuRight($authorId = 0, $aryArticleId = array(), $typeView = 0) {
        /* @var $articleMgr Smscom_ArticleManager */
        $articleMgr = $this->backend->getManager('Article');

        // setting the value to display new icon
        $this->setDateShowIconNew();

        // このオーサーの新着記事
        if ($typeView > 0) {
            $limit = $this->config->get('carezine_article_listauthor_limit_new_list_by_author_right');
            $limitRankingArticle = (is_numeric($limit) && $limit > 0) ? (int)$limit : 10;
        } else {
            $limit = $this->config->get('menu_right_limit_article_ranking');
            $limitRankingArticle = (is_numeric($limit) && $limit > 0) ? (int)$limit : 5;
        }
        

        $aryNewArticle = $articleMgr->getNewArticle($limitRankingArticle, 0, 0, (int)$authorId, 0, false, $aryArticleId);
        if (Ethna::isError($aryNewArticle)) {
            $this->ae->add(null, 'DBの登録でエラーがありました', null);
        }

        $this->af->setApp('listArticleNewSideRight', $aryNewArticle['list']);
        $this->af->setApp('author_id', $authorId);
        $this->af->setApp('typeView', $typeView);
    }

    /**
     * setDateShowIconNew 
     * 
     */
    function setDateShowIconNew() {
        $dateShowIcon = $this->config->get('carezine_date_show_icon_new');
        $dateShowIcon = ((int)$dateShowIcon < 1) ? 7 : $dateShowIcon;

        $this->af->setApp('timestampArticleNewTerm', date(strtotime("-{$dateShowIcon} day")));
    }
    // #14 End Luvina Modify
    // #13 Start Luvina Modify
    /**
     * Check file batch is running 
     * @author Luvina
     * @access public
     * @param unknown_type $cmd
     * @param unknown_type $scriptName
     * @param unknown_type $prams
     */
    function chkProcess($cmd, $scriptName = null, $prams = null) {
        if($scriptName == null) {
            $_pid = getmypid();
            $cmd = sprintf($cmd, $_SERVER['PHP_SELF'] . $prams);
            $_command = "{$cmd} | grep -v '{$_pid}' | grep -v 'grep'";
        } else {
            $cmd = sprintf($cmd, $scriptName . $prams);
            $_command = "{$cmd} | grep -v 'grep'";
        }

        $_command_output = array();
        $_command_ret = null;

        exec( $_command, $_command_output, $_command_ret );
        if( count($_command_output) === 0){
            return true;
        }

        return false;
    }

    function setListRankingSideRightCarenews($dateLimitRanking = 0) {
        /* @var $objCarenews Smscom_CarenewsManager */
        $objCarenews = $this->backend->getManager('Carenews');

        // get list access ranking side right
        $limit = $this->config->get('carenews_side_right_limit_ranking_news');
        $limitRankingSideRight = (is_numeric($limit) && $limit > 0) ? (int)$limit : 5;

        if($dateLimitRanking <= 0) {
            // Get newterm for ranking
            $dateLimitRanking = $this->config->get('carenews_date_limit_ranking');
            $dateLimitRanking  = ($dateLimitRanking < 1) ? 7 : $dateLimitRanking;
        }
        $this->af->setApp('limitDateRanking', $dateLimitRanking);

        $listAccessRanking = $objCarenews->getListRankingViewCareNews($limitRankingSideRight, $dateLimitRanking);
        if (Ethna::isError($listAccessRanking)) {
            $this->ae->add('', 'DBエラーが発生しました');
            return 'carenews_details';
        }

        $this->af->setApp('listAccessRanking', $listAccessRanking);
    }
    // #13 End Luvina Modify
    // #25 Start Luvina Modify
    /**
     * setDateShowIconNew 
     * 
     */
    function carenewsSetDateShowIconNew() {
        $dateShowIcon = $this->config->get('carenews_date_show_icon_new');
        $dateShowIcon = ((int)$dateShowIcon < 1) ? 7 : $dateShowIcon;

        $this->af->setApp('timestampShowIconNew', date(strtotime("-{$dateShowIcon} day")));
    }
    // #25 End Luvina Modify
    /**
     * 
     * 
     */
    function checkCarenew() {
        $actualLink = getenv('REQUEST_URI');
        $pattern = '/smp-app=true/';
        $checkExist = preg_match($pattern, $actualLink, $matches);
        if($checkExist) {
            $this->af->setApp('url_check_exist', true);
        } else {
            $this->af->setApp('url_check_exist', false);
        }
    }

    // #30 Start Luvina Modify
    /**
     * Validate datetime input yyyy-mm-dd H:i:s 
     * @param string $dateTime
     * @return boolean
     */
    function checkDatetime($dateTime){
        $matches = array();
        if(preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $dateTime, $matches)){
            $dd = trim($matches[3]);
            $mm = trim($matches[2]);
            $yy = trim($matches[1]);
            return checkdate($mm, $dd, $yy);
        } else {
            return false;
        }
    }
    // #30 End Luvina Modify
    // #51 Start Luvina Modify
    function getRemoteAddr () {
        //IPアドレスを取得
        $ip = (@$_SERVER['HTTP_X_FORWARDED_FOR']) ? @$_SERVER['HTTP_X_FORWARDED_FOR'] : getenv('REMOTE_ADDR');
        return $ip;
    }
    
    // #51 End Luvina Modify
    // #68 CT-CM Start Luvina Modify
    function replaceCharacter($string, $strReplace = '', $aryPattern = array()) {
        if(!empty($string) && is_array($aryPattern) && count($aryPattern)) {
            $patten = join('|', $aryPattern);
            mb_regex_encoding('UTF-8');
            $string = mb_ereg_replace($patten, $strReplace, $string);
        }

        return $string;
    }
    // #68 CT-CM End Luvina Modify
    // #33 Start Luvina Modify
    /**
     * setSideBarMagazine
     * @author Luvina
     * @param 
     * @return 
     */
    function setSideBarMagazine(){
        $limitConfCarezine = $this->config->get('limit_carezine_top');
        $limitCarezine = (is_numeric($limitConfCarezine) && $limitConfCarezine > 0) ? (int)$limitConfCarezine : 7;

        /* @var $articleMgr Smscom_ArticleManager */
        $articleMgr = $this->backend->getManager('Article');

        $aryNewArticle = $articleMgr->getNewArticle($limitCarezine);
        if (Ethna::isError($aryNewArticle)) {
            $this->ae->add(null, 'DBの登録でエラーがありました', null);
            return 'tool_category';
        }
        $this->af->setApp('listCarezine', $aryNewArticle['list']);
        // Get list carenews asccess ranking
        $this->setListRankingSideRightCarenews();
    }
    // #33 End Luvina Modify
}
// }}}
?>
