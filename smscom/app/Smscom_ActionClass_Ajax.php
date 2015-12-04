<?php

/**
 *  Smscom_ActionClass_Ajax.php
 *
 *  @author     Luvina
 *  @package    Smscom
 *  @version    1.0
 */
 
// #38 Start Luvina modify
require_once('lib/JSON.php');
// #38 Start Luvina modify

// PEAR::Services_JSONの読込み
// require_once 'lib/JSON.php';

/**
 *  action実行クラス
 *
 *  @author     Luvina
 *  @package    Smscom
 *  @access     public
 */
class Smscom_ActionClass_Ajax extends Smscom_ActionClass {
    // jsonオブジェクト
    var $json = null;

    // response用プロパティ
    var $response = array(
        'status_code' => 0, // 0:正常、1:エラー
        'content'     => null,
        'message'     => null
    );

    /**
     *  アクションの前処理
     *
     *  @author    Luvina
     *  @access    public
     *  @return    string  Forward先(正常終了ならnull)
     */
    function prepare() {
        $this->json = new Services_JSON;
        return null;
    }

    /**
     * Smartyレンダラオブジェクトの取得（Ajax用）
     * 
     *  @author    Luvina
     *  @access    public
     */
    function _getTemplateEngine() {
        $renderer = $this->backend->ctl->getTemplateEngine();

        $renderer->engine->left_delimiter = '<%';
        $renderer->engine->right_delimiter = '%>';

        $renderer->engine->plugins_dir[] = 'plugins_ex/';

        $renderer->setPlugin('url','function','smarty_function_url_ex');
        $renderer->setPlugin('form','block','smarty_block_form_ex');
        $renderer->setPlugin('form_input','function','smarty_function_form_input_ex');

        $renderer->setPlugin('istyle','function','smarty_function_istyle');
        $renderer->setPlugin('form_value','function','smarty_function_form_value');
        $renderer->setPlugin('thumb','modifier','smarty_modifier_thumb');
        $renderer->setPlugin('emoji2img','modifier','smarty_modifier_emoji2img');
        $renderer->setPlugin('emoji_strip','modifier','smarty_modifier_emoji_strip');
        $renderer->setPlugin('convert_external_link','modifier','smarty_modifier_convert_external_link');
        $renderer->setPlugin('replace_session_tag','modifier','smarty_modifier_replace_session_tag');
        $renderer->setPlugin('message','function','smarty_function_message2');

        return $renderer;
    }
    
    // $5 Start Luvina Modify
    function setDefaultTemplateDir($template)
    {
        $renderer = $this->backend->ctl->getTemplateEngine();
        
        /* @var $ctl Smscom_Controller_Base */
        $ctl =& Ethna_Controller::getInstance();
        $this->smarty = &$renderer->getEngine();
        
        if($ctl->isUseSmartphone()) {
            if (!is_readable($renderer->getTemplateDir() . $template)) {
                // use template of PC
                $ctl->disableUseSmartphone(true);
                $renderer->setTemplateDir($ctl->getTemplatedir());
                $this->smarty->template_dir = $ctl->getTemplatedir();
            } else {
                $this->smarty->compile_dir = $ctl->getDirectory('template_csp');
                // 一応がんばってみる
                if (is_dir($this->smarty->compile_dir) === false) {
                    Ethna_Util::mkdir($this->smarty->compile_dir, 0755);
                }
            }
        }
    }
    // $5 End Luvina Modify

    /**
     * エラーチェック
     * 
     *  @author    Luvina
     *  @access    public
     */
    function _isError($result) {
        if (Ethna::isError($result)) {
            Smscom_DB_PEAR::handleError($result);
            $this->_setError('エラーが発生しました。しばらくしてから実行し直してください。');
        }
    }

    /**
     * エラーをセットして処理を終了
     * 
     *  @author    Luvina
     *  @access    public
     */
    function _setError($message) {
        $this->response['status_code'] = 1;

        if (is_array($message)) {
            foreach ($message as $e) {
                $this->response['message'] .= $e . "\n";
            }
        } else {
            $this->response['message'] = $message;
        }

        $this->_returnResponse();
    }

    /**
     * レスポンスとしてJSONを返す
     * 
     *  @author    Luvina
     *  @access    public
     */
    function _returnResponse() {
        header("Content-Type: text/javascript; charset=utf-8");
        echo $this->json->encode($this->response);

        exit();
    }

}
?>
