<?php
// vim: foldmethod=marker
/**
 *  Smscom_ActionForm_AdminGlossary.php
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
class Smscom_ActionForm_AdminGlossary extends Smscom_ActionForm
{
    /**#@+
     *  @access private
     */

    /** @var    array   フォーム値定義(デフォルト) */
    var $form_template = array(
        'id'                        => array(
            // フォームの定義
            'type'          => VAR_TYPE_INT,
            'form_type'     => FORM_TYPE_HIDDEN,
            'name'          => 'ID',
            // バリデート
            'required'      => false,
        ),
        'display_type'              => array(
            'type'          => VAR_TYPE_INT,                // 入力値型
            'form_type'     => FORM_TYPE_RADIO,             // フォーム型
            'name'          => '公開・非公開',              // 表示名
            'option'        => 'GlossaryCategories, display_type',
            'default'       => 1,                           // デフォルト
            'required'      => true,
            'inattr'        => 'GlossaryCategories, display_type',
        ),
        'glossary_category_name'    => array(
            'type'          => VAR_TYPE_STRING,
            'form_type'     => FORM_TYPE_TEXT,
            'name'          => 'カテゴリ名',
            'required'      => true,
            'min'           => NULL,
            'max'           => 30,
            'custom'        => 'checkVendorChar',           // 機種依存文字・不適切表現
        ),
        'glossary_category_body'    => array(
            'type'          => VAR_TYPE_STRING,
            'form_type'     => FORM_TYPE_TEXTAREA,
            'name'          => '説明',
            'required'      => true,
            'min'           => NULL,
            'max'           => 4000,
            'custom'        => 'checkVendorChar',           // 機種依存文字・不適切表現
        ),
        'glossary_name'             => array(
            'type'          => VAR_TYPE_STRING,
            'form_type'     => FORM_TYPE_TEXT,
            'name'          => 'キーワード',
            'required'      => 'true',
            'min'           => NULL,
            'max'           => 50,
            'custom'        => 'checkVendorChar',           // 機種依存文字・不適切表現
        ),
        'glossary_name_kana'        => array(
            'type'          => VAR_TYPE_STRING,
            'form_type'     => FORM_TYPE_TEXT,
            'name'          => '読みがな',
            'required'      => 'true',
            'min'           => NULL,
            'max'           => 200,
            'custom'        => 'checkVendorChar',           // 機種依存文字・不適切表現
        ),
        'glossary_index'            => array(
            'type'          => VAR_TYPE_STRING,
            'form_type'     => FORM_TYPE_TEXT,
            'name'          => '索引',
            'required'      => 'true',
            'min'           => NULL,
            'max'           => 1,
            'custom'        => 'checkVendorChar',           // 機種依存文字・不適切表現
            'inattr'        => 'Glossary,glossary_index',
        ),
        'glossary_body'             => array(
            'type'          => VAR_TYPE_STRING,
            'form_type'     => FORM_TYPE_TEXTAREA,
            'name'          => '説明文',
            'required'      => 'true',
            'min'           => NULL,
            'max'           => 4000,
            'custom'        => 'checkVendorChar',           // 機種依存文字・不適切表現
        ),
        'glossary_category'         => array(
            'type'          => VAR_TYPE_INT,
            'form_type'     => FORM_TYPE_SELECT,
            'name'          => 'カテゴリ',
            'option'        => 'GlossaryCategories,glossary_category_name',
            'required'      => 'true',
            'inattr'        => 'GlossaryCategories,glossary_category_name',
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
