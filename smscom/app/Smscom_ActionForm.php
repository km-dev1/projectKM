<?php
// vim: foldmethod=marker
/**
 *  Smscom_ActionForm.php
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
class Smscom_ActionForm extends Ethna_ActionForm
{
    /**#@+
     *  @access private
     */

    /** @var    array   フォーム値定義(デフォルト) */
    var $form_template = array();

    /** @var    bool    バリデータにプラグインを使うフラグ */
    var $use_validator_plugin = true;

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
    
    /**
     *  ユーザ定義検証メソッド(CSRFのチェック)
     *
     *  @access protected
     */
    function _validatePlus()
    {
        if (!empty($this->check_csrf_id)) {
            if (!Ethna_Util::isCsrfSafe()) {
                $this->ae->add(null, '不正なリクエストです。手順の始めからやり直してください');
            }
        }
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

        $this->Str_Pos_Kinshi($name);

        return $null;
    }

    /**
     *  チェックメソッド: 入力文字内容チェック(部分一致)
     *
     *  @access public
     *  @param  string  $name   フォーム項目名
     *  @return object  Ethna_Error エラーオブジェクト(エラーが無い場合はnull)
     */
    function &Str_Pos_Kinshi($name){
        $null = null;
        $str = $this->form_vars[$name];

        $base_dirs = $this->backend->getConfig()->get('base_path');

        $bubun_file = $base_dirs."/etc/strstr.txt";

        $fp = fopen($bubun_file,"r");
        if($fp){

            while (!feof($fp)) {
                $data = fgets($fp, filesize($bubun_file));
                if(@strstr($str,trim($data))){
                    return $this->ae->add($name,'{form}に不適切な表現['.trim($data).']があります。内容を確認し、修正をお願いします', E_FORM_INVALIDCHAR);
                    break;
                }
            }
/*
            while(($data = fgetcsv($fp,filesize($bubun_file))) !== false){
                if(strstr($str,$data[0])){


                    return $this->ae->add($name,'{form}に不適切な表現があります。内容を確認し、修正をお願いします', E_FORM_INVALIDCHAR);
                    break;
                }
            }
*/            return $null;
        }else{
            return $null;
        }
    }
    
    /**
     *  チェックメソッド: メールアドレス
     *
     *  @access public
     *  @param  string  $name   フォーム項目名
     *  @return object  Ethna_Error エラーオブジェクト(エラーが無い場合はnull)
     */
    function &checkMailaddress($name)
    {
        $null = null;
        $form_vars = $this->check($name);
        
        if ($form_vars == null) {
            return $null;
        }
        
        foreach ($form_vars as $v) {
            if ($v === "") {
                continue;
            }
            if (Ethna_Util::checkMailaddress($v) == false) {
                return $this->ae->add($name,
                    '{form}の書式が正しくありません', E_FORM_INVALIDCHAR);
            }
            if ($this->backend->config->get('mailaddress_dns_check') && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) {
                if (!checkdnsrr(substr(strstr(trim(chop($v)), "@"), 1, strlen(strstr(trim(chop($v)), "@"))))) {
                    return $this->ae->add($name,'{form}の書式が正しくありません', E_FORM_INVALIDCHAR);
                }
            }
        }
        
        return $null;
    }
    
    /**
     *  チェックメソッド: 絵文字のが含まれているかどうかの確認
     *
     *  @access public
     *  @param  string  $name   フォーム項目名
     *  @return object  Ethna_Error エラーオブジェクト(エラーが無い場合はnull)
     */
    function checkEmojiChar($name)
    {
        $null = null;
        $form_vars = $this->check($name);
        
        if ($form_vars == null) {
            return $null;
        }
        
        foreach ($form_vars as $v) {
            if ($v === "") {
                continue;
            }
            if (Mobile_Util::emoji_count($v) > 0) {
                return $this->ae->add($name,
                    '{form}には絵文字を使用することはできません', E_FORM_INVALIDCHAR);
            }
        }
        
        return $null;
        
    }
    
    /**
     *  フォーム値変換フィルタ: 空文字ならnullに変換
     *
     *  @access protected
     *  @param  mixed   $value  フォーム値
     *  @return mixed   変換結果
     */
    function _filter_empty2null($value)
    {
        $value = empty($value) ? null : $value;
        return $value;
    }
    
    /**
     *  フォーム値変換フィルタ: 改行コード・TABをすべて削除→文字列前後の半角・全角スペースを削除
     *
     *  @access protected
     *  @param  mixed   $value  フォーム値
     *  @return mixed   変換結果
     */
    function _filter_linetrim($value)
    {
        mb_regex_encoding('UTF-8');
        $value = mb_ereg_replace('[\r\n\t\v\0]', '', $value);
        $value = mb_ereg_replace('^[ 　]*(.*?)[ 　]*$', '\1', $value);
        
        return $value;
    }
    
    /**
     *  FCKEditor用フォーム値変換フィルタ: 自動で入力されてしまう<br />タグを除去
     *
     *  @access protected
     *  @param  mixed   $value  フォーム値
     *  @return mixed   変換結果
     */
    function _filter_fckStripBr($value)
    {
        return preg_replace('/^<br \/>$/', '', $value);
    }
}
// }}}
?>
