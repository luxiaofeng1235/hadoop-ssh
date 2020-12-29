
<?php
class TFT {
    //换行符
    public $crlf;
    //列分隔符
    public $tab;
    //全部列名,表头
    public $format;
    //单行最大长度
    public $maxlength;
    //分析时用于过滤的列名
    public $mapfield;
    //用于过滤的列值里所有合法字符值
    public $mapchar;
    //合法字符值的个数
    private $countChar;
    //本实例要读取的过滤列值里的字符值, 
    //不合法字符统一给符合最后一个字符值的实例
    public $mychar;
    //输入是否压缩
    public $gzip;
    //内部缓存下的列名
    private $fkey;
    //内部缓存下的列数，验证有效性用
    private $countKey;
    //打开后的文件handle
    private $fh;

    function __construct($fname,$conf = array()) {
        $this->crlf = isset($conf['crlf']) ? $conf['crlf'] : "\n";
        $this->tab = isset($conf['tab']) ? $conf['tab'] : "\t";
        $this->maxlength = isset($conf['maxlength']) ? $conf['maxlength'] : 1048576;
        $this->mapchar = empty($conf['mapchar']) ? 
            str_split("0123456789abcdefghijklmnopqrstuv") : str_split($conf['mapchar'])  ;
        $tis->countChar = count($this->mapchar);
        $this->mychar = empty($conf['mychar']) ? null : $conf['mychar'];


        if (empty($conf['format']))
            throw new Exception("no line format");
        $this->fkey = preg_split('/\s+/',$conf['format'],PREG_SPLIT_NO_EMPTY);
        if (empty($this->fkey))
            throw new Exception("line format empty");
        if (isset($conf['mapfield']) && in_array($conf['mapfield'],$this->fkey)) {
            $this->mapfield = $conf['mapfield'];
        } else {
            throw new Exception("mapfiled format mismatch");
        }
        $this->countKey = count($this->fkey);
        if ($conf['gzip']) {
            $this-> = popen("zcat $fname","r");
        } else {
            $this->fh = fopen($fname,"r");
        }
        if (!is_resource($this->fh)) 
            throw new Exception("fopen failed : $fname");

        register_shutdown_function(array(&$this, '__destruct'));
    }
    function __destruct() {
        if (is_resource($this->fh))
            fclose($this->fh);
    }
    function readline() {
        while (!feof($this->fh)) {
            $line = stream_get_line($this->fh,$this->maxlength,$this->crlf);
            $arr = explode($this->tab,$line);
            if (count($arr) != $this->countKey) {
                return null;
            }
            $ret = array();
            for($i = 0 ; $i<$this->countKey ; $i++) {
                $ret[$this->fkey[$i]] = $arr[$i];
            }
            if (is_null($this->mychar))
                return $ret;
            $char = substr($ret[$this->mapfield],-1);
            if ($char == $this->mychar)
                return $ret;
            if (!in_array($char,$this->mapchar) && $char == $this->mapchar[$this->countChar]) {
                return $ret;
            } else {
                continue;
            }
        }
        return null;
    }
}
