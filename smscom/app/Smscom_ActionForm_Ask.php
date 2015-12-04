<?php
require_once 'Smscom_ReferenceTypes.php';

class Smscom_ActionForm_Ask extends Smscom_ActionForm
{
    var $form_template = array(
       'company_name' => array(
           'type'              => VAR_TYPE_STRING,
           'form_type'         => FORM_TYPE_TEXT,
           'name'              => '貴社名',
           'filter'            => 'linetrim',
           'required'          => true,
           'max'               => 50,
           'custom'            => 'checkVendorChar,checkEmojiChar',
        ),
       'charge_name' => array(
           'type'              => VAR_TYPE_STRING,
           'form_type'         => FORM_TYPE_TEXT,
           'name'              => 'ご担当者様氏名',
           'filter'            => 'linetrim',
           'required'          => true,
           'max'               => 20,
           'custom'            => 'checkVendorChar,checkEmojiChar',
        ),
       'user_name' => array(
           'type'              => VAR_TYPE_STRING,
           'form_type'         => FORM_TYPE_TEXT,
           'name'              => 'お名前',
           'filter'            => 'linetrim',
           'required'          => true,
           'max'               => 20,
           'custom'            => 'checkVendorChar,checkEmojiChar',
        ),
       'mail_address' => array(
           'type'              => VAR_TYPE_STRING,
           'form_type'         => FORM_TYPE_TEXT,
           'name'              => 'メールアドレス',
           'filter'            => 'linetrim',
           'istyle'            => 3,
           'required'          => true,
           'max'               => 100,
           'custom'            => 'checkMailaddress',
        ),
       'mail_address_2' => array(
           'type'              => VAR_TYPE_STRING,
           'form_type'         => FORM_TYPE_TEXT,
           'name'              => 'メールアドレス(確認用)',
           'filter'            => 'linetrim',
           'istyle'            => 3,
           'required'          => true,
           'max'               => 100,
           'custom'            => 'checkMailaddress',
       ),
       'reference_type_id' => array(
           'type'              => VAR_TYPE_INT,
           'form_type'         => FORM_TYPE_RADIO,
           'name'              => 'お問い合わせの種類',
           'option'            => null,
           'required'          => true,
       ),
       'message_body' => array(
           'type'              => VAR_TYPE_STRING,
           'form_type'         => FORM_TYPE_TEXTAREA,
           'name'              => 'ご質問・ご相談内容',
           'required'          => true,
           'min'               => null,
           'max'               => 4000,
           'custom'            => 'checkVendorChar,checkEmojiChar',
       ),
       'reference_tel' => array(
           'type'              => VAR_TYPE_STRING,
           'form_type'         => FORM_TYPE_TEXT,
           'name'              => '電話番号',
           'filter'            => 'linetrim',
           'istyle'            => 4,
           'required'          => true,
           'min'               => null,
           'max'               => 15,
           'regexp'            => '/^[0-9\-]+$/',
       )
    );

    //@var bool  バリデータにプラグインを使うフラグ
    var $use_validator_plugin = true;

    //フォーム値検証のエラー処理
    function handleError($name, $code)
    {
        return parent::handleError($name, $code);
    }

    /**
     *  フォーム値定義テンプレートを設定する
     *  @param  array   $form_template  フォーム値テンプレート
     *  @return array   フォーム値テンプレート
     */
    function _setFormTemplate($form_template)
    {
        return parent::_setFormTemplate($form_template);
    }

    /**
     *  チェックメソッド: 機種依存文字(UTF-8)
     *
     *  @access public
     *  @param  string  $name   フォーム項目名
     *  @return object  Ethna_Error エラーオブジェクト(エラーが無い場合はnull)
     */
    function &checkVendorChar($name)
    {
        $null = null;
        $string = mb_convert_encoding($this->form_vars[$name], 'eucjp-win', 'utf-8');

        for ($i = 0, $j = 0; $i < strlen($string); $i++, $j++) {
            /* JIS13区のみチェック */
            $c = ord($string{$i});
            if ($c < 0x80) {
                /* ASCII */
            } else if ($c == 0x8e) {
                /* 半角カナ */
                $i++;
//>>
//            } else if ($c == 0x8f) {
//                /* JIS X 0212 */
//                $i += 2;
            } else if ($c == 0x8f && $i < strlen($string) - 2 && (
                    (ord($string{$i + 1}) == 0xa2 && ord($string{$i + 2}) == 0xf1) ||
                    (ord($string{$i + 1}) == 0xf3 && ord($string{$i + 2}) >= 0xf3 && ord($string{$i + 2}) <= 0xfe) ||
                    (ord($string{$i + 1}) == 0xf4 && ord($string{$i + 2}) >= 0xa1 && ord($string{$i + 2}) <= 0xfe)
                )) {
                return $this->ae->add($name,
                    '{form}に機種依存文字「' . mb_substr($this->form_vars[$name], $j, 1, 'utf-8') . '」が入力されています', E_FORM_INVALIDCHAR);
//<<
            } else if ($c == 0xad || ($c >= 0xf9 && $c <= 0xfc)) {
                /* IBM拡張文字 / NEC選定IBM拡張文字 */
                return $this->ae->add($name,
                    '{form}に機種依存文字「' . mb_substr($this->form_vars[$name], $j, 1, 'utf-8') . '」が入力されています', E_FORM_INVALIDCHAR);
            } else {
                $i++;
            }
        }

        return $null;
    }

    //フォーム値定義設定
    function _setFormDef()
    {
        return parent::_setFormDef();
    }
}
// }}}
?>
