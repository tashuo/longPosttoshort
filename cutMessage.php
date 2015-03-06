<?php
/**
* 将长的主贴分割为一个主贴与若干个回帖
*/
Class longPostToShort{
	//传入的信息，每段默认的长度，当前取的长度
	private $message, $per_counts, $per_now_counts;

	//截取开始时的位置
	private $start;

	//定义的特殊标签
	private $arr_tags;
	private $arr_special_tags;

	//处理后的message
	private $retMsg = '';

	//定义默认的截断字符串的长度
	const PER_COUNT = 300;


	public function __construct($message, $per_counts = 0){
		mb_internal_encoding('UTF-8');

		$this->message = $message;
		if($per_counts == 0){
			$per_counts = self::PER_COUNT;
		}

		//初始化每次取的长度
		$this->per_counts = $per_counts;
		$this->per_now_counts = $per_counts;

		$this->start = 0;

		//所有的标签
		$this->arr_tags = array(
					 '[font]',
					 '[color]',
					 '[size]',
					 '[b]',
					 '[i]',
					 '[align]',
					 '[ul]',
					 '[li]',
					 '[u]',
					 '[attach]',
					 '[attachimg]',
					 '[media]',
					 '[audio]',
					 '[flash]',
					 '[hide]',
					 '[swf]',
					 '[img]',
					 '[/font]',
					 '[/color]',
					 '[/size]',
					 '[/b]',
					 '[/i]',
					 '[/align]',
					 '[/ul]',
					 '[/li]',
					 '[/u]',
					 '[/attach]',
					 '[/attachimg]',
					 '[/media]',
					 '[/audio]',
					 '[/flash]',
					 '[/hide]',
					 '[/swf]',
					 '[/img]',
					 );

		//部分特殊的标签，即标签之间长度可能很长
		$this->arr_special_tags = array(
						 '[font]',
					 	 '[color]',
					 	 '[size]',
					 	 '[b]',
					 	 '[i]',
					 	 '[align]',
					 	 '[u]',
					 	 );
	}

	public function reverseCut(){
		//开始标签数组
		static $arr_tags_begin = array();
		
		//未匹配到开始标签的结束标签
		static $arr_tags_not_match = array();

		//存储分割好的字符串数组
		static $arr_message = array();

		//临时存储未闭合标签字符串
		static $str_tmp = '';

		//存储已经连续匹配的次数，用以之后主动闭合部分标签
		static $times = 0;

		//如果传入的数据为空
		if(empty($this->message)){
			return $this->retData(0);
		}

		//如果传入的字符串长度比每段截取长度短直接返回传入的数据
		if(mb_strlen($this->message) <= $this->per_counts){
			return $this->retData(0);
		}

		//超过一定次数，且未闭合标签全部是特殊标签的话特殊处理，主动闭合
		if($times >2 && $this->isAllSpecialTags($arr_tags_begin)){
			//先主动闭合临时变量中的字符串
			$str_tmp = $this->closeBeginTags($arr_tags_begin, $str_tmp);

			//次数清零
			$times = 0;
			//将临时变量中的字符串和当前取得的字符串存入到最终数组中
			$arr_message[] = $str_tmp;

			//重置临时变量临时变量
			$str_tmp = implode('', $arr_tags_begin);

			//重置未匹配标签数组
			$arr_tags_not_match = array();
			$this->reverseCut();
		}else{
			//截取相应长度的数据
			$per_message = mb_substr($this->message, $this->start, $this->per_now_counts);
	
			if(!empty($per_message)){
				//判断每条信息结尾是否截断了特殊字符
    				$retData = $this->checkSpecialCutted($per_message);
    				if($retData['code'] == 1){
    					$times ++;
	
    					//修改当前截取长度为返回的位置数值
    					$this->per_now_counts = $retData['pos'];
    					$this->reverseCut();
    				}else{
    					//获取到所有匹配的tag数组
					preg_match_all("/\[\/?\w+\]/isU", $per_message, $match);
					if(count($match) > 0){
						foreach($match as $tmp){
							foreach($tmp as $tag){
								if(in_array($tag, $this->arr_tags)){
									if(strpos($tag, '[/') === FALSE){
										array_push($arr_tags_begin, $tag);
									}else{
										if(!empty($arr_tags_begin)){
											$pop_tag = array_pop($arr_tags_begin);
											if(strpos($pop_tag,substr($tag, strpos($tag, '/')+1)) !== FALSE){
												#正常匹配
											}else{
												#未匹配到开始标签
												array_push($arr_tags_begin, $pop_tag);
												array_push($arr_tags_not_match, $tag);
											}
										}
										
									}
								}
							}
						}
					}
		
					//判断是否存在未匹配完的开始标签，有的话继续匹配;
					//没有的话清空未匹配的结束标签数组，并且存入相应数组，继续下一次分割
					if(count($arr_tags_begin) > 0){
						$times ++;
						//将当前取的数据存入临时变量
						$str_tmp .= $per_message;
	
						
					}else{
						//如果开始标签数组已经为空，则清除掉未结束标签数组
						if(count($arr_tags_not_match) > 0){
							$arr_tags_not_match = array();
						}
						//次数清零
						$times = 0;
						//将临时变量中的字符串和当前取得的字符串存入到最终数组中
						$arr_message[] = $str_tmp.$per_message;
						//清空临时变量
						$str_tmp = '';
					}
	
					//取下一段数据，修改开始位置，重置截取长度为默认长度
					$this->start += $this->per_now_counts;
					$this->per_now_counts = $this->per_counts;
					//进行下一次截取
					$this->reverseCut();
    				}
				
			}else{
				//将最后剩余的临时变量中的数据处理后存入最终数组中
				if(!empty($str_tmp)){
					//主动闭合未匹配到结束标签的开始标签
					$str_tmp = $this->closeBeginTags($arr_tags_begin, $str_tmp);
					$arr_tags_begin = $arr_tags_not_match = array();
					$arr_message[] = $str_tmp;
				}

				$this->retMsg = $arr_message;
			}

			return $this->retData(1);
		}


		
	}
	
    	//检测是否结尾处刚好截断了特殊字符
    	private function checkSpecialCutted($message){
    		//截断的是特殊字符
    		if(($pos = mb_strrpos($message, '[')) !== FALSE && $this->checkPartOfSpecial(mb_substr($message, $pos)) && mb_strrpos($message, ']', $pos) === FALSE){
    			return array(
    				        'code' => 1,
    				        'pos' => $pos,
    				        );
    		}
	
    		return array(
    			        'code' => 0
    			       );
    	}

    	//检测字符串是否是某个特殊标签
    	private function checkPartOfSpecial($str){
    		foreach($this->arr_tags as $tag){
    			if(strpos($tag, $str) !== FALSE){
    				return TRUE;
    			}
    		}
    		return FALSE;
    	}

    	//主动闭合剩余的未闭合标签
    	private function closeBeginTags($arr_tag, $str){
    		if(is_array($arr_tag) && !empty($str)){
    			while($pop_tag = array_pop($arr_tag)){
    				$str .= '[/'.mb_substr($pop_tag, 1);
    			}
    			return $str;
    		}
    	}

    	//判断开始标签数组中的标签是否全部是特殊的标签
    	private function isAllSpecialTags($arr_tag){
    		if(is_array($arr_tag)){
    			foreach($arr_tag as $k=>$v){
    				if(!in_array($v, $this->arr_special_tags)){
    					return FALSE;
    				}
    			}
    			return TRUE;
    		}
    	}


    	private function retData($code = 0){
    		switch ($code) {
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
    	}
}


    $str='成什么样……“她是谁[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全部加精，[/color]衬得[/eeattach]她大家升级，好这位林二狸精！”大家多留言，我全部加精，然后，大家升级，好有推荐票……端木守志脸红了，吱吱多留言，我全部加精，然后，大家升级，好有推荐票……端木守志多留言，我全部加精，然后，大家升级，好有推荐票……端木守志多留言，我全部加精，然后，大家升级，好有推荐票……端木守志脸红了，吱吱唔唔正要有脸红了，吱吱唔唔正要有脸红了，吱吱唔唔正多留言，我全部加精，然后，大家升级，好有多留言，我全部加精，然后，大家升级，好有推荐票……端木守志脸红了，吱吱唔唔正要有推荐票……端木守多留言，我全部大家升级，好有推荐票……端木守志脸红了，吱吱唔唔正要有脸红了，吱吱唔唔正要有脸红了，吱吱唔唔正多留言，我全部加精，然后，大家升级，好有多留言，我全部加精，然后，大家升级，好有推荐票……端木守志脸红了，吱吱唔唔正要有推荐票……端木守多留言，我全部加精，然后，大家升级，好有推荐票……端木守志脸红了，吱吱唔唔正要有志脸红了，吱吱唔二狸精！”大家多留言，我全部加精，然后，大家升级，好有推荐票……端木守志脸红了，吱吱多留言，我全部加精，然后，大家升级，好有推荐票……端木守志多留言，我全部加精，然后，大家升级，好有推荐票……端木守志多留言，我全部加精，然后，大家升级，好有推荐票……端木守志脸红唔正要有要有唔唔正要有[/font]推荐票……端木娇[/attach]然后，大家升级，好有推荐票……端木守志脸更红了，吱吱唔唔正要解释，只听到远处传来声娇俏的招呼：“守志[wwa道。这就是林氏双姝中小的那个了。李思浅仔细打量眼前的小姑娘：嘴唇微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的ttach]表哥！”随声音而来的，是一个十四五岁的小姑娘，一身明丽的海棠红衬得[/tttattach]她娇艳欲滴。“这是林家二表姐，闺名明月。”端木睛介绍道。这就是林氏双姝中小的那个了。李思浅仔细打量眼前的小姑娘：嘴唇微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型。这位林二成什么样……“她是谁？狐狸精！大家多留言，我全部加精，然后，大家升级，好有推荐票……端木守志脸更红了，吱吱唔唔正要解释，[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全部加精，[/color]衬得[/eeattach]她大家升级，好有[/font]推荐票只听到远处传来声娇俏的招呼：“守志[attach]表哥！”随声音而来的，是一个十四五岁的小姑娘，一身明丽的海棠红衬得[/attach]她娇艳欲滴。
　　“这是林家二表姐，闺名明月。”端木睛介绍道。
　　这就是林氏双姝中小的那个了[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全部加精，[/color]衬得[/eeattach]她大家升级，好有[/font]推荐票。李思浅仔细打量眼前的小姑娘：嘴唇微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型。
　　这位林二成什么样……
　　“她是谁？狐成什么样……
　　“她是谁？狐狸精！”
大家多留言，我全部加精，然后，大家升级，好有推荐票……
　　端木守志脸更红了，吱吱唔唔正要解[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全部加精，[/color]衬得[/eeattach]她大家升级，好有[/font]推荐票释，只听到远处传来声娇俏的招呼：“守志[attach]表哥！”
　　随声音而来的，是一个十四五岁的小姑娘，一身明丽的海棠红衬得[/attach]她娇艳欲滴。
　　“这是林家二表姐，闺名明月。”端木睛介绍道。
　　这就是林氏双姝中小的[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全部加精，[/color]衬得[/eeattach]她大家升级，好有[/font]推荐票那个了。李思浅仔细打量眼前的小姑娘：嘴唇微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型。
　　这位林二成什么样……
　　“她是谁？狐狸精！”
大家多留言，我全部加精，然后[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全部加精，[/color]衬得[/eeattach]她大家升级，好有[/font]推荐票，大家升级，好有推荐票……
　　端木守志脸更红了，吱吱唔唔正要解释，只听到远处传来声娇俏的招呼：“守志[attach]表哥！”
　　随声音而来的，是一个十四五[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全部加精，[/color]衬得[/eeattach]她大家升级，好有[/font]推荐票岁的小姑娘，一身明丽的海棠红衬得[/attach]她娇艳欲滴。
　　“这是林家二表姐，闺名明月。”端木睛介绍道。
　　这就是林氏双姝中小的那个了。李思[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全部加精，[/color]衬得[/eeattach]她大家升级，好有[/font]推荐票浅仔细打量眼前的小姑娘：嘴唇微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型。
　　这位林二狸精！”
大家多留言，我全部加精，然后，大家升级，好有推荐票……
　　端木守志脸更红了，吱吱唔唔正要[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全部加精，[/color]衬得[/eeattach]她大家升级，好有[/font]推荐票解释，只听到远处传来声娇俏的招呼：“守志[attach]表哥！”
　　随声音而来的，是一个十四五岁的小姑娘，一身明丽的海棠红衬得[/attach]她娇艳欲滴。
　　“这是林家二表姐，闺名明月。”端木睛介绍道。
　　这就是林氏双姝中小的那个了。李思浅[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全部加精，[/color]衬得[/eeattach]她大家升级，好有[/font]推荐票仔细打量眼前的小姑娘：嘴唇浅[font]？狐[color]狸精[u]！”[b]大家多微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型。
　　这位大家多微嘟、杏眼桃腮，非常漂亮位大家多微嘟、杏眼桃腮，非常漂多微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的多微嘟、杏眼桃腮，非常漂亮位大家多微嘟、杏眼桃腮，非常漂多微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的多微嘟、杏眼桃腮，非常漂亮位大家多微嘟、杏眼桃腮，非常漂多微嘟、杏眼桃腮，非常漂亮，是那种娇憨可爱的类型林二;';

    // $str = '成什么样[img]……“她是谁[font]？狐[color]狸精[u]！”[b]大家多[/b]留言[/u]，[qqattach]我全[/color]部加[/font]精全[/img]娇';

    $test = new longPostToShort($str, 200);
    $arr_message = $test->reverseCut();
    foreach($arr_message['message'] as $key => $val){
    	echo $key.": \n".$val."\n";
    }
    // var_dump($test->reverseCut());
    // var_dump($test->cutMessage());

