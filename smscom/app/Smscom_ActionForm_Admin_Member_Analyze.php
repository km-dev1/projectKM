<?php
// vim: foldmethod=marker
/**
 *  Smscom_ActionForm_AdminAnalyze.php
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
class Smscom_ActionForm_Admin_Member_Analyze extends Smscom_ActionForm
{
    /**#@+
     *  @access private
     */

    /** @var    array   フォーム値定義(デフォルト) */
    var $form_template = array(
        'sex_type' => array(
            'type'              => VAR_TYPE_INT,
            'form_type'         => FORM_TYPE_SELECT,
            'name'              => '性別',
            'option'            => 'UserProfile,sex_type',
            'required'          => false,
        ),
        'age_from' => array(
            'type'              => VAR_TYPE_INT,
            'form_type'         => FORM_TYPE_TEXT,
            'name'              => '年齢',
            'required'          => false,
        ),
        'age_to' => array(
            'type'              => VAR_TYPE_INT,
            'form_type'         => FORM_TYPE_TEXT,
            'name'              => '年齢',
            'required'          => false,
        ),
        'prefecture_cd' => array(
            'type'              => VAR_TYPE_INT,
            'form_type'         => FORM_TYPE_SELECT,
            'name'              => '都道府県',
            'option'            => 'UserProfile,prefecture_id',
            'required'          => false,
            'inattr'            => 'UserProfile,prefecture_id',
        ),
        'work_type_cd' => array(
            'type'              => VAR_TYPE_INT,
            'form_type'         => FORM_TYPE_SELECT,
            'name'              => '勤務先概要種別',
            'option'            => 'WorkTypes,WorkTypes',
            'required'          => false,
            'inattr'            => 'WorkTypes,WorkTypes',
        ),
        'work_service_cd' => array(
            'type'              => VAR_TYPE_INT,
            'form_type'         => FORM_TYPE_SELECT,
            'name'              => '勤務先概要サービス',
            'option'            => 'WorkServices,WorkServices',
            'required'          => false,
            'inattr'            => 'WorkServices,WorkServices',
        ),
        'year' => array(
            'type'              => VAR_TYPE_INT,
            'form_type'         => FORM_TYPE_HIDDEN,
            'name'              => '年',
            'min'               => 1998,                            //TODO min 年を指定する？
        ),
        'month' => array(
            'type'              => VAR_TYPE_INT,
            'form_type'         => FORM_TYPE_HIDDEN,
            'name'              => '月',
            'min'               => 1,
            'max'               => 12,
            'required'          => false,
        ),
        'mode' => array(
            'type'              => VAR_TYPE_STRING,
            'form_type'         => FORM_TYPE_HIDDEN,
            'name'              => 'モード',
        ),
        'csv_download_password' => array(
            'type'              => VAR_TYPE_STRING,
            'form_type'         => FORM_TYPE_PASSWORD,
            'name'              => 'パスワード',
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
