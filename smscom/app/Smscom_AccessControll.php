<?php
/**
 *  Smscom_AccessControll.php
 *
 *  @author     {$author}
 *  @package    Smscom
 */
define('LOOP_TRY_AUTO_LOGIN', 3);
/**
 *  Smscom_AccessControll
 *
 *  @author     {$author}
 *  @access     public
 *  @package    Smscom
 */
class Smscom_AccessControll
{
    /**#@+
     *  @access private
     */

    var $backend;

    var $action_form;
    var $af;

    var $action_error;
    var $ae;

    var $session;

    var $auto_login_expiry;

    var $easy_login_expiry;

    /**#@-*/

    /**
     *  コンストラクタ
     *
     *  @access public
     *  @param  object  Ethna_Backend  バックエンドオブジェクト
     */
    function Smscom_AccessControll($backend)
    {
        $this->backend = $backend;

        $this->action_form = $this->backend->getActionForm();
        $this->af = $this->action_form;

        $this->action_error = $this->backend->getActionError();
        $this->ae = $this->action_error;

        $this->session = $this->backend->getSession();

        $this->auto_login_expiry = $this->backend->getConfig()->get('auto_login_expiry');

        $this->easy_login_expiry = $this->backend->getConfig()->get('easy_login_expiry');
    }

    /**
     *  管理ページエリアにアクセス中か
     *
     *  @access public
     */
    function isAdminArea()
    {
        if (!preg_match('/admin/', $_SERVER['REQUEST_URI'])) {
            return false;
        }

        return true;
    }

    /**
     *  会員ページエリアにアクセス中か
     *
     *  @access public
     */
    function isMemberArea()
    {
        if (!preg_match('/member/', $_SERVER['REQUEST_URI'])) {
            return false;
        }

        return true;
    }

    /**
     *  ログインしているか
     *
     *  @access public
     */
    function isLogin()
    {
        if ($this->session->isStart() && $this->session->get('is_login') == 1) {
            return true;
        }

        return false;
    }

    /**
     *  管理者かどうか
     *
     *  @access public
     */
    function isAdmin()
    {
        if ($this->isLogin() &&
           ($this->session->get('authorization_type') == 2 || $this->session->get('authorization_type') == 3)) {
            return true;
        }

        return false;
    }

    /**
     *  会員かどうか（管理者含む）
     *
     *  @access public
     */
    function isMember()
    {
        if ($this->isLogin() &&
           ($this->session->get('authorization_type') == 1 || $this->session->get('authorization_type') == 2 || $this->session->get('authorization_type') == 3)) {
            return true;
        }

        return false;
    }

    /**
     *  自動ログインを実行する
     *
     *  @access public
     *  @return bool    true:自動ログイン成功 false:自動ログイン失敗
     */
    function tryAutoLogin()
    {
        if ($this->isLogin()) {
            return true; // すでにログイン済み
        }
        
        if (Mobile_Util::isMobile()) {
            // モバイルの自動ログイン処理
            $carrier = '';
            if (Mobile_Util::isDoCoMo()) {
                // docomoは誤作動の原因になるので自動ログインはおこなわない
                // $carrier = 'docomo';
            } else if (Mobile_Util::isEZweb()) {
                $carrier = 'ezweb';
            } else if (Mobile_Util::isSoftBank()) {
                $carrier = 'softbank';
            }
            if (empty($carrier)) {
                // 対応したキャリアでない
                //$this->backend->log(LOG_DEBUG, "###@@@自動ログイン：対応したキャリアでない");
                return false;
            }
            
            $uid = Mobile_Util::getUID();
            if (empty($uid)) {
                // UIDが取得できない
                //$this->backend->log(LOG_DEBUG, "###@@@自動ログイン：UIDが取得できない");
                return false;
            }
            
            $result = $this->easyLogin($carrier, $uid);
            //if (!$result) $this->backend->log(LOG_DEBUG, "###@@@自動ログイン：自動ログイン失敗");
            return $result;
        }
        
        // PCの自動ログイン処理
        if (empty($_COOKIE['auto_login'])) {
            return false; // 自動ログイン用のクッキーがセットされていない
        }
        $auto_login_key = $_COOKIE['auto_login'];

        $db = $this->backend->getDB('')->db;
        $db_r = $this->backend->getDB('r')->db;

        // 自動ログインキーがDBに登録されているか
        $sql = "SELECT
                    *
                FROM
                    t_auto_login
                WHERE
                    auto_login_key = ? AND
                    expire >= NOW()";
                    
        $index = 1;
        while ($index <= LOOP_TRY_AUTO_LOGIN) {
            $result = $db_r->query($sql, array($auto_login_key));
            if (Ethna::isError($result)) {
                if ($index < LOOP_TRY_AUTO_LOGIN) {
                    $index++;
                	continue;
                } else {
                    Smscom_DB_PEAR::handleError($result);
                    return false;
                }
            } else {
                break;
            }
        }
        
        $t_auto_login = $result->fetchRow(DB_FETCHMODE_ASSOC);

        // 自動ログインが成功しようが失敗しようが、ここで一旦設定を削除しておく
        $this->_disableAutoLogin();

        if ($t_auto_login) {
            // 認証を行う
            $sql = "SELECT
                        *
                    FROM
                        m_user_profile
                    WHERE
                        user_id = ? AND
                        display_type = 1 AND
                        delete_flg = 0";
            $index = 1;

            while ($index <= LOOP_TRY_AUTO_LOGIN) {
                $result = $db_r->query($sql, array($t_auto_login['user_id']));
                if (Ethna::isError($result)) {
                    if ($index < LOOP_TRY_AUTO_LOGIN) {
                        $index++;
                        continue;
                    } else {
                        Smscom_DB_PEAR::handleError($result);
                        return false;
                    }
                } else {
                    break;
                }
            }

            if (($user_profile = $result->fetchRow(DB_FETCHMODE_ASSOC))) {
                $mail_address = $user_profile['mail_address'];
                $password = $user_profile['password'];

                $blowfish = new Crypt_Blowfish($this->backend->config->get('crypt_key'));
                $password = $blowfish->decrypt(pack('H*', $password));

                if ($this->login($mail_address, $password, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     *  メルマガからの自動ログインを実行する
     *
     *  @access public
     *  @return bool    true:自動ログイン成功 false:自動ログイン失敗
     */

    function mmAutoLogin() 
    {   
        // ログインチェック
        if ($this->isLogin()) {
            return true;
        }  
    
        $db = $this->backend->getDB('');
        $db_r = $this->backend->getDB('r');
            
        // URLよりパラメータ取得
        $mmc = isset($_GET['mmc']) ? $_GET['mmc'] : null;
        $mmk = isset($_GET['mmk']) ? $_GET['mmk'] : null;
           
        if (is_null($mmc) || is_null($mmk)) {
            return false;
        }

        $crypt_key = $this->backend->config->get('crypt_key');
        $blowfish = new Crypt_Blowfish($crypt_key.$mmk);
        $mmc = trim($blowfish->decrypt(pack('H*', $mmc)));    
        list($unique_key, $expiry) = explode(' ', $mmc, 2); 
            
        // 有効期限のチェック
        if ($expiry < time()) {
            return false;
        }
        // ユニークキーから対応するuser_idを取得
        $sql = "SELECT user_id FROM t_mm_login WHERE unique_key = ?";
        $result = $db->db->query($sql, array($unique_key));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }
        $result = $result->fetchRow(DB_FETCHMODE_ASSOC);
        $user_id = $result['user_id'];
        // 認証 ～ ポイント（ログイン/メルマガからのログイン）付与までトランザクション処理開始
        $db->db->autocommit(false);
        $db->begin();
            
        // ログイン処理
        if (!$this->mm_login($user_id)) {
	        return false;
        }
        // ポイント(ログイン)加算処理
        $point_obj = $this->backend->getManager('Point');
        $result = $point_obj->addPointDataByAction($user_id, 'LOGIN');
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            $db->rollback();
            return false;
        }
        // ポイント（メルマガからのログイン）加算処理
        $point_action_key = 'MAIL_LOGIN';
        $result = $point_obj->addPointDatabyAction($user_id, $point_action_key);
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            $db->rollback();
            return false;
        }
        
        $db->commit();
        return true;
    }

    function mm_login($user_id, $set_auto_login=false, $admin=false)
    {
        $db   = $this->backend->getDB('');
        $db_r = $this->backend->getDB('r');
        
        // ログイン時のタイムスタンプを取得
        $loginTimestamp = time();
        
        // CY,TH,CT,CMの場合の認証処理
        $sql = "SELECT * FROM m_user_profile WHERE user_id = ? AND display_type = 1 AND delete_flg = 0";
        $result = $db_r->db->query($sql, array($user_id));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }
        if (!$result->numRows()) {
            return false;
        }
        $user_profile = $result->fetchRow(DB_FETCHMODE_ASSOC);
        
        // すでにログイン済みの場合は一旦ログアウト
        if ($this->isLogin()) {
            $this->logout();
        }
        // セッションを一旦破棄
        if ($this->session->isStart()) {
            $this->session->destroy();
        }

        // セッション開始
        $this->session->start();
        $this->session->set('is_login', 1);
        $this->session->set('user_id', $user_profile['user_id']);
        $this->session->set('authorization_type', $user_profile['authorization_type']);
        $this->session->set('user_name', $user_profile['nickname']);
        $this->session->set('user_image_file', $user_profile['user_image_file']);
        $this->session->set('total_point', $user_profile['total_point']);
            
        // 最終ログイン日、ログイン数を更新
        $sql = "UPDATE
                    m_user_profile
                SET
                    last_login_date = now(),
                    login_count = login_count + 1
                WHERE
                    user_id = ?";
        $result = $db->db->query($sql, array($user_profile['user_id']));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }

        if (Mobile_Util::isMobile()) {
            // モバイルでログインした際に、メール投稿キーを確認し、設定がなければ新たに生成する
            $user_postmail_mgr = $this->backend->getManager('UserPostmail');
            $user_postmail_mgr->setUserPostkeyIfNotExist($user_profile['user_id']);
          
            // かんたんログイン設定があるかどうかのフラグをセッションに格納
            $is_set_easylogin = $this->isEasyLoginSettingExist($user_profile['user_id'], true);
            $this->session->set('is_set_easylogin', $is_set_easylogin);
        }
        // ログイン記録
        $mobileType = (Mobile_Util::isMobile()) ? 1 : 0;
        $ipAddress  = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (Mobile_Util::isMobile()) {
         $carrier = '';
            if (Mobile_Util::isDoCoMo()) {
                $carrier = 'docomo';
            } else if (Mobile_Util::isEZweb()) {
                $carrier = 'ezweb';
            } else if (Mobile_Util::isSoftBank()) {
                $carrier = 'softbank';
            }
        }
            $uid = Mobile_Util::getUID();
        $moinfo = $carrier ." ".$uid;
        $loginLogsManager = $this->backend->getManager('LoginLogs');
        $result = $loginLogsManager->addLoginLogs($user_profile['user_id'], $mobileType, $ipAddress, $ua, $moinfo, $loginTimestamp );
        if (Ethna::isError($result)) {
            //エラーの場合、処理は止めずにログだけ残す。
        } 
        return true;
    }  

    /**
     *  ログインする
     *
     *  @access public
     *  @return bool    true:認証成功 false:認証失敗
     */
    function login($mail_address, $password, $set_auto_login=false, $admin=false)
    {
        //ログイン時のタイムスタンプ
        $loginTimestamp = time();

        if (empty($mail_address) || empty($password)) {
            return false;
        }

        $db = $this->backend->getDB('')->db;
        $db_r = $this->backend->getDB('r')->db;

        // 認証処理
        $blowfish = new Crypt_Blowfish($this->backend->config->get('crypt_key'));
        $password = $blowfish->encrypt($password);
        $password = bin2hex($password);
        //print_r((extension_loaded('mcrypt') ? '1:' : '0:') . $password);

        $sql = "SELECT
                    *
                FROM
                    m_user_profile
                WHERE
                    mail_address = ? AND
                    password = ? AND
                    display_type = 1 AND
                    delete_flg = 0";

        $result = $db_r->query($sql, array($mail_address, $password));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }
        if (!$result->numRows()) {
            return false;
        }

        $user_profile = $result->fetchRow(DB_FETCHMODE_ASSOC);

        // 管理サイトには管理者のみログインできる
        if ($admin) {
            if (!($user_profile['authorization_type'] == 2 || $user_profile['authorization_type'] == 3)) {
                return false;
            }
        }

        // すでにログイン済みの場合は一旦ログアウト
        if ($this->isLogin()) {
            $this->logout();
        }
        // セッションを一旦破棄
        if ($this->session->isStart()) {
            $this->session->destroy();
        }

        // セッション開始
        //$this->session->start(36400); // @fixme
        $this->session->start();
        $this->session->set('is_login', 1);
        $this->session->set('user_id', $user_profile['user_id']);
        $this->session->set('authorization_type', $user_profile['authorization_type']);
        $this->session->set('user_name', $user_profile['nickname']);
        $this->session->set('user_image_file', $user_profile['user_image_file']);
        $this->session->set('total_point', $user_profile['total_point']);
        // DBに格納されているプロフィール画像を画像ディレクトリに書き出す
        /*
        if ($user_profile['user_image_file'] != '') {
            $fname = Smscom_Util::joinPath($this->backend->getConfig()->get('document_root'), $this->backend->getConfig()->get('user_image_path')) . md5($user_profile['user_id']);
            // @fixme:ファイルのロックは？
            if (($fp = fopen($fname, 'wb'))) {
                $img = stripslashes($user_profile['user_image_file']);
                fwrite($fp, $img, strlen($img));
                fclose($fp);
                $this->session->set('user_image_file', $fname);
            } else {
                $this->session->set('user_image_file', '');
            }
        }
        */

        // 最終ログイン日、ログイン数を更新
        $sql = "UPDATE
                    m_user_profile
                SET
                    last_login_date = now(),
                    login_count = login_count + 1
                WHERE
                    user_id = ?";
        $result = $db->query($sql, array($user_profile['user_id']));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }

        // 自動ログインを再設定
        if ($set_auto_login) {
            $this->_enableAutoLogin();
        }

        // モバイルでログインした際に、メール投稿キーを確認し、設定がなければ新たに生成する
        if (Mobile_Util::isMobile()) {
            $user_postmail_mgr = $this->backend->getManager('UserPostmail');
            $user_postmail_mgr->setUserPostkeyIfNotExist($user_profile['user_id']);
            
            // かんたんログイン設定があるかどうかのフラグをセッションに格納
            $is_set_easylogin = $this->isEasyLoginSettingExist($user_profile['user_id'], true);
            $this->session->set('is_set_easylogin', $is_set_easylogin);
        }

        // ポイント加算処理
        $db = $this->backend->getDB('');
        $db->db->autocommit(false);
        //$db->begin();

        $result = $this->backend->getManager('Point')->addPointDataByAction($user_profile['user_id'], 'LOGIN');
        if (Ethna::isError($result)) {
            $db->db->rollback();
            //$db->rollback();
            //エラーの場合、処理は止めずにログだけ残す。
            //$this->ae->add('', 'DBの登録でエラーがありました');
        } else {
            $db->db->commit();
            //$db->commit();
        }
        $db->db->autocommit(true);

        // アクセス解析用のログイン記録
        $mobileType = (Mobile_Util::isMobile()) ? 1 : 0;
        $ipAddress  = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (Mobile_Util::isMobile()) {
         $carrier = '';
            if (Mobile_Util::isDoCoMo()) {
                $carrier = 'docomo';
            } else if (Mobile_Util::isEZweb()) {
                $carrier = 'ezweb';
            } else if (Mobile_Util::isSoftBank()) {
                $carrier = 'softbank';
            }
        }
            $uid = Mobile_Util::getUID();
        $moinfo = $carrier ." ".$uid;
        $loginLogsManager = $this->backend->getManager('LoginLogs');
        $result = $loginLogsManager->addLoginLogs($user_profile['user_id'], $mobileType, $ipAddress, $ua, $moinfo, $loginTimestamp );
        if (Ethna::isError($result)) {
            //エラーの場合、処理は止めずにログだけ残す。
        }

        return true;
    }

    /**
     *  ログアウトする
     *
     *  @access public
     */
    function logout()
    {
        // 自動ログイン設定を削除
        $this->_disableAutoLogin();
        
        if (Mobile_Util::isMobile()) {
            // モバイルの場合はかんたんログインの設定を解除
            $user_id = $this->session->get('user_id');
            $this->unsetEasyLogin($user_id);
        }
        
        // セッションを破棄
        $this->session->destroy();
        
        // #5 Start Luvina Modify
        setcookie('user_id', '', time() - 60*60*24*30 , '/');
        // #5 End Luvina Modify
        
    }

    function isEasyLoginSettingExist($user_id, $check_expire=true)
    {
        $db_r = $this->backend->getDB('r')->db;
        $sql;
        $param;

        if ($check_expire) {
            // 有効期限をチェックする場合
            $sql = "SELECT
                        *
                    FROM
                        t_easy_login
                    WHERE
                        user_id = ? AND
                        update_date + INTERVAL ? SECOND >= NOW()";

            $param = array($user_id, $this->easy_login_expiry);

        } else {
            // 有効期限をチェックしない場合
            $sql = "SELECT
                        *
                    FROM
                        t_easy_login
                    WHERE
                        user_id = ?";

            $param = array($user_id);
        }

        $result = $db_r->query($sql, $param);
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }
        if (!$result->numRows()) {
            return false;
        }

        return true;
    }

    function setEasyLogin($user_id, $carrier, $uid)
    {
        if (empty($user_id) || empty($carrier) || empty($uid))
            return false;

        $db = $this->backend->getDB('')->db;
        $sql;
        $param;
        
        // 重複するUIDの登録はすべて削除する（あとから設定したものが有効）
        $sql = "DELETE FROM
                    t_easy_login
                WHERE
                    user_id <> ? AND
                    carrier = ? AND
                    uid = ?";

        $result = $db->query($sql, array($user_id, $carrier, $uid));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }

        if (!$this->isEasyLoginSettingExist($user_id, false)) {
            // 新規登録
            $sql ="INSERT INTO
                        t_easy_login (user_id, carrier, uid, update_date)
                   VALUES
                        (?, ?, ?, NOW())";

            $param = array($user_id, $carrier, $uid);

        } else {
            // 更新
            $sql = "UPDATE
                        t_easy_login
                    SET
                        carrier = ?,
                        uid = ?,
                        update_date = NOW()
                    WHERE
                        user_id = ?";

            $param = array($carrier, $uid, $user_id);
        }

        $result = $db->query($sql, $param);
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }

        // セッションのかんたんログイン設定フラグを立てる
        $this->session->set('is_set_easylogin', true);

        return true;
    }

    function unsetEasyLogin($user_id)
    {
        if (empty($user_id))
            return false;

        $db = $this->backend->getDB('')->db;

        $sql = "DELETE FROM
                    t_easy_login
                WHERE
                    user_id = ?";

        $result = $db->query($sql, array($user_id));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }

        // セッションのかんたんログイン設定フラグを下げる
        $this->session->set('is_set_easylogin', false);

        return true;
    }

    /**
     *  かんたんログインを実行する
     *
     *  @access public
     *  @return bool    true:自動ログイン成功 false:自動ログイン失敗
     */
    function easyLogin($carrier, $uid)
    {
        if (empty($carrier) || empty($uid)) {
            return false;
        }

        $db = $this->backend->getDB('')->db;
        $db_r = $this->backend->getDB('r')->db;

        // かんたんログイン設定がDBに登録されているか
        $sql = "SELECT
                    user_id
                FROM
                    t_easy_login
                WHERE
                    carrier = ? AND
                    uid = ? AND
                    update_date + INTERVAL ? SECOND >= NOW()";

        $result = $db_r->getOne($sql, array($carrier, $uid, $this->easy_login_expiry));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }
        if (empty($result)) {
            return false;
        }
        
        $user_id = $result;

        // 認証を行う
        $sql = "SELECT
                    *
                FROM
                    m_user_profile
                WHERE
                    user_id = ? AND
                    display_type = 1 AND
                    delete_flg = 0";
        $result = $db_r->query($sql, array($user_id));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return false;
        }
        if (($user_profile = $result->fetchRow(DB_FETCHMODE_ASSOC))) {
            $mail_address = $user_profile['mail_address'];
            $password = $user_profile['password'];

            $blowfish = new Crypt_Blowfish($this->backend->getConfig()->get('crypt_key'));
            $password = $blowfish->decrypt(pack('H*', $password));

            if ($this->login($mail_address, $password)) {

                // かんたんﾛｸﾞｲﾝ設定の更新時間を更新する
                $sql = "UPDATE
                            t_easy_login
                        SET
                            update_date = NOW()
                        WHERE
                            user_id = ?";
                $result = $db->query($sql, array($user_id));
                if (Ethna::isError($result)) {
                    Smscom_DB_PEAR::handleError($result);
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    /**
     *  自動ログインを設定する
     *
     *  @access private
     */
    function _enableAutoLogin()
    {
        $auto_login_key = sha1(uniqid() . mt_rand(1, 999999999));

        $db = $this->backend->getDB('')->db;

        // DBに自動ログインのエントリを追加
        $sql ="INSERT INTO
                    t_auto_login (auto_login_key, user_id, expire)
               VALUES
                    (?, ?, ?)";
        $result = $db->query($sql,
            array($auto_login_key, $this->session->get('user_id'), date('Y-m-d H:i:s', time() + $this->auto_login_expiry)));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            return;
        }
        
        // 自動ログイン用のクッキーをセット
        setcookie('auto_login', $auto_login_key, time() + $this->auto_login_expiry, '/');
    }

    /**
     *  自動ログイン設定を削除する
     *
     *  @access private
     */
    function _disableAutoLogin()
    {
        if (empty($_COOKIE['auto_login'])) {
            return; // 自動ログイン用のクッキーがセットされていない
        }
        $auto_login_key = $_COOKIE['auto_login'];

        // DBから自動ログインのエントリを削除
        $db = $this->backend->getDB('')->db;
        $sql = "DELETE FROM t_auto_login WHERE auto_login_key = ?";
        $result = $db->query($sql, array($auto_login_key));
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
        }

        // 自動ログイン用のクッキーを削除
        //setcookie('auto_login');
        setcookie("auto_login", '', time() - 3600, '/');
    }
    // #495 Start Luvina Modify
    /**
     *  Check deny access
     *  @author Luvina
     *
     *  @access public
     *  @return bool
     */
    function checkDenyAccess() {
        if ($_COOKIE['deny_access_flg'] == 1) {
        	return true;
        }
        return false;
    }
    // #495 End Luvina Modify
}
?>
