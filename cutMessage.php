<?php
/**
* 将长的主贴分割为一个主贴与若干个回帖
*/
Class longPostToShort{
	private $message, $per_counts, $start;
	//处理后的message
	private $retMsg;

	public function __construct($message, $per_counts){
		mb_internal_encoding('UTF-8');

		$this->message = $message;
		$this->per_counts = $per_counts;
		$this->start = 0;
	}

    	//截取字符串到数组, 默认标签无嵌套
    	public function cutMessage(){
    		//传入的message为空
    		if($this->message == ''){
    			return $this->retData(-2);
    		}

    		//传入的per_counts小于100
    		if($this->per_counts < 100){
    			return $this->retData(-1);
    		}

    		//传入的message长度不大于per_counts值
    		if(mb_strlen($this->message) <= $this->per_counts){
    			return $this->retData(0);
    		}

    		static $ret_arr = array();
    		$per_message = mb_substr($this->message, $this->start, $this->per_counts);
	
    		if(!empty($per_message)){
    			//判断每条信息结尾是否截断了特殊字符
    			$retData = $this->checkSpecialCutted($per_message);
    			//0、开始标签被截断， 1、闭合标签被截断， 2、未被截断
    			if($retData['code'] == 1){
    				$per_message = mb_substr($this->message, $this->start, $retData['pos']);
    				$ret_arr[] = $per_message;

    				//递归调用
    				$this->start = $this->start+$retData['pos'];
    				$this->cutMessage();
    			}elseif($retData['code'] ==2){
    				$next_fifteen_message = mb_substr($this->message, $this->start + $this->per_counts, 15);
    				if(($pos = mb_strpos($next_fifteen_message, ']')) !== FALSE){
    					$per_message = mb_substr($this->message, $this->start, $this->per_counts + $pos +1);
    					$ret_arr[] = $per_message;

    					//递归调用
    					$this->start = $this->start + $this->per_counts + $pos +1;
    					$this->cutMessage();
    				}
    			}elseif($retData['code'] == 0){
    				if(!empty($special = $this->checkSpecialchars($per_message))){
    					$next_message = mb_substr($this->message, $this->start+$this->per_counts, $this->per_counts);
					
    					//获取中括号内的字符串
    					$speical = mb_substr($special, 1, -1);
    					if(($pos = mb_strpos($next_message, $speical)) !== FALSE){
    						$per_message = mb_substr($this->message, $this->start, $this->per_counts+$pos+mb_strlen($special)-1);
    						
    						$ret_arr[] = $per_message;

    						//递归调用
    						$this->start = $this->start+$this->per_counts+$pos+mb_strlen($special)-1;
    						$this->cutMessage();
    					}else{
    						//下一段文字中标签依然没有闭合，应该做出相应处理
    						//some codes...
    						$ret_arr[] = $per_message;

    						//递归调用
    						$this->start = $this->start + $this->per_counts;
    						$this->cutMessage();
    					}	
    				}else{
    					$ret_arr[] = $per_message;

    					//递归调用
    					$this->start = $this->start + $this->per_counts;
    					$this->cutMessage();
    				}
    			}
    		}
	
    		$this->retMsg = $ret_arr;
    		return $this->retData(1);
    	}
	
    	//返回逆序获取到的第一个特殊字符
    	private function checkSpecialchars($message){
    		$specialchars = array('[font]',
    					'[attach]',
    					'[attachimg]',
    					);
	
    		$tmp_speical = '';
	
    		foreach($specialchars as $speical){
    			if(mb_strrpos($message, $speical) !== FALSE){
    				$tmp_speical = $speical;
    				break;
    			}
    		}
	
    		return $tmp_speical;
    	}
	
    	//检测是否结尾处刚好截断了特殊字符,暂不判断截断的中括号中是否是特殊字符
    	private function checkSpecialCutted($message){
    		$specialchars = array('[font]',
    					'[attach]',
    					'[attachimg]',
    					);
    		//截断的是特殊字符的开始标签或闭合标签
    		if(($pos = mb_strrpos($message, '[/')) !== FALSE && $pos>90 && mb_strrpos($message, ']', $pos) === FALSE){
    			return array(
    				        'code' => 2,
    				        'pos' => $pos,
    				        );
    		}elseif(($pos = mb_strrpos($message, '[')) !== FALSE && $pos>90 && mb_strrpos($message, ']', $pos) === FALSE){
    			return array(
    				        'code' => 1,
    				        'pos' => $pos,
    				        'word' => mb_substr($message, $pos),
    				        );
    		}
	
    		return array(
    				'code' => 0
    			       );
    	}


    	private function retData($code){
    		switch ($code) {
    			case '-2':
    			case '-1':
    			case '0':
    				return array(
    					       'code' => 0,
    					       'message' => $this->message,
    					       );
    				break;
    			case '1':
    				return array(
    					       'code' => 1,
    					       'message' => $this->retMsg,
    					       );
    				break;
    		}
    		exit;
    	}
}


    $str='成什么样……“她是谁？狐狸精！”
大家多留言，我全部加精，然后，大家升级，好有推荐票……
　　端木守志脸更红了，吱吱唔唔正要解释，只听到远处传来声娇俏的招呼：“守志[attach]表哥！”
　　随声音而来的，是一个十四五岁的小姑娘，一身明丽的海棠红衬得[/attach]她娇艳欲滴。
　　“这是林家二表姐，闺名明月。”端木睛介绍道。
　　这就是林氏双姝中小的那个了。李思浅仔细打量眼前的小姑娘：嘴唇微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型。
　　这位林二成什么样……
　　“她是谁？狐狸精！”
大家多留言，我全部加精，然后，大家升级，好有推荐票……
　　端木守志脸更红了，吱吱唔唔正要解释，只听到远处传来声娇俏的招呼：“守志[attach]表哥！”
　　随声音而来的，是一个十四五岁的小姑娘，一身明丽的海棠红衬得[/attach]她娇艳欲滴。
　　“这是林家二表姐，闺名明月。”端木睛介绍道。
　　这就是林氏双姝中小的那个了。李思浅仔细打量眼前的小姑娘：嘴唇微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型。
　　这位林二成什么样……
　　“她是谁？狐成什么样……
　　“她是谁？狐狸精！”
大家多留言，我全部加精，然后，大家升级，好有推荐票……
　　端木守志脸更红了，吱吱唔唔正要解释，只听到远处传来声娇俏的招呼：“守志[attach]表哥！”
　　随声音而来的，是一个十四五岁的小姑娘，一身明丽的海棠红衬得[/attach]她娇艳欲滴。
　　“这是林家二表姐，闺名明月。”端木睛介绍道。
　　这就是林氏双姝中小的那个了。李思浅仔细打量眼前的小姑娘：嘴唇微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型。
　　这位林二成什么样……
　　“她是谁？狐狸精！”
大家多留言，我全部加精，然后，大家升级，好有推荐票……
　　端木守志脸更红了，吱吱唔唔正要解释，只听到远处传来声娇俏的招呼：“守志[attach]表哥！”
　　随声音而来的，是一个十四五岁的小姑娘，一身明丽的海棠红衬得[/attach]她娇艳欲滴。
　　“这是林家二表姐，闺名明月。”端木睛介绍道。
　　这就是林氏双姝中小的那个了。李思浅仔细打量眼前的小姑娘：嘴唇微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型。
　　这位林二狸精！”
大家多留言，我全部加精，然后，大家升级，好有推荐票……
　　端木守志脸更红了，吱吱唔唔正要解释，只听到远处传来声娇俏的招呼：“守志[attach]表哥！”
　　随声音而来的，是一个十四五岁的小姑娘，一身明丽的海棠红衬得[/attach]她娇艳欲滴。
　　“这是林家二表姐，闺名明月。”端木睛介绍道。
　　这就是林氏双姝中小的那个了。李思浅仔细打量眼前的小姑娘：嘴唇微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型。
　　这位林二;';

    $test = new longPostToShort($str, 100);
    var_dump($test->cutMessage());

