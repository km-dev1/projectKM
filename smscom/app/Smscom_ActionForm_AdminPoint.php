<?php
// vim: foldmethod=marker
/**
 *  Smscom_ActionForm_Adminpoint.php
 *
 *  @author     {$author}
 *  @package    Smscom
 *  @version    $Id: app.actionform.php 323 2006-08-22 15:52:26Z fujimoto $
 */

// {{{ Smscom_ActionForm
/**
 *  アクションフォームクラス
 *
 *  @author     {$author}
 *  @package    Smscom
 *  @access     public
 */
class Smscom_ActionForm_AdminPoint extends Smscom_ActionForm
{
    /**#@+
     *  @access private
     */

    /** @var    array   フォーム値定義(デフォルト) */
    var $form_template = array(

        'point' => array(
            // フォームの定義
            'type'          => array(VAR_TYPE_INT),
            'form_type'     => FORM_TYPE_TEXT,
            'name'          => '加算ポイント',
            'required'      => true,
            'min'           => 0,
            'max'           => 500,
            'regexp'        => null,
        ),

        'limit_count' => array(
            // フォームの定義
            'type'          => array(VAR_TYPE_INT),
            'form_type'     => FORM_TYPE_TEXT,
            'name'          => '上限回数',
            'required'      => true,
            'min'           => 0,
            'max'           => 9999,
            'regexp'        => null,
        ),

        'ins_point' => array(
            // フォームの定義
            'type'          => VAR_TYPE_INT,
            'form_type'     => FORM_TYPE_TEXT,
            'name'          => '加算ポイント',
            'required'      => true,
            'min'           => 0,
            'max'           => 500,
            'regexp'        => null,
        ),

        'ins_limit_count' => array(
            // フォームの定義
            'type'          => VAR_TYPE_INT,
            'form_type'     => FORM_TYPE_TEXT,
            'name'          => '上限回数',
            'required'      => true,
            'min'           => 0,
            'max'           => 9999,
            'regexp'        => null,
        ),


        'point_action_key' => array(
            // フォームの定義
            'type'          => VAR_TYPE_STRING,
            'form_type'     => FORM_TYPE_TEXT,
            'name'          => 'ID',
            'required'      => true,
            'min'           => null,
            'max'           => 30,
            'regexp'        => '/^[0-9a-zA-Z_-]+$/',
        ),

        'aciton_name' => array(
            // フォームの定義
            'type'          => VAR_TYPE_STRING,
            'form_type'     => FORM_TYPE_TEXT,
            'name'          => 'アクション名',
            'required'      => true,
            'min'           => null,
            'max'           => 100,
            'regexp'        => null,
            'custom'        => 'checkVendorChar',
        ),

        'mode' => array(
            // フォームの定義
            'type'          => VAR_TYPE_STRING,
            'form_type'     => FORM_TYPE_HIDDEN,
            'required'      => true,
            'min'           => null,
            'max'           => null,
            'regexp'        => null,
        ),


    );

    /** @var    bool    バリデータにプラグインを使うフラグ */
    var $use_validator_plugin = true;
    /** @var    bool    追加検証強制フラグを設定する */
    var $force_validate_plus = true;
    
    /**#@-*/
    
    /**
     *  ユーザ定義検証メソッド(フォーム値間の連携チェック等)
     *
     *  @access protected
     */
    function _validatePlus()
    {
        // プロジェクト共通のバリデーション実行のために親クラスのメソッドを呼び出す
        parent::_validatePlus();
    }



    /**#@-*/

    /**
     *  フォーム値検証のエラー処理を行う
     *
     *  @access public
     *  @param  string      $name   フォーム項目名
     *  @param  int         $code   エラーコード
     */
    function handleError($name, $code)
    {
        return parent::handleError($name, $code);
    }

    /**
     *  フォーム値定義テンプレートを設定する
     *
     *  @access protected
     *  @param  array   $form_template  フォーム値テンプレート
     *  @return array   フォーム値テンプレート
     */
    function _setFormTemplate($form_template)
    {
        return parent::_setFormTemplate($form_template);
    }

    /**
     *  フォーム値定義を設定する
     *
     *  @access protected
     */
    function _setFormDef()
    {
        return parent::_setFormDef();
    }
}
// }}}
?>
