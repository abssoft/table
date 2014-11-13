<?php  

class Table {
	var $HTMLcontent = '';
	var $TRcontent=array();
	var $TRows=0;
	var $Cols=array();
	var $Colgroups=array();
	var $insideRow=false;
	var $insideHead=false;
	var $maxCells=0;
	var $tab="\t";
	var $newline="\n";
	var $emptycell="";
	var $autocolspan=true;
    	var $nohtml=false;

	function Table($params = '',$id='',$newline="",$tab=""){
		$this->newline=$newline;
		$this->tab=$tab;
		$this->HTMLcontent= '<table';
		if (!empty($id)) $this->HTMLcontent .= ' id="' . trim($id).'"';
		if (!empty($params)) $this->HTMLcontent .= ' ' . trim($params);
		$this->HTMLcontent .= '>'.$this->newline;
	}
	

	static function create(array $structure,$params=''){
		$T=new self($params);
		foreach ($structure as $row_id=>$cells){
			$T->TR();
			foreach ($cells as $cell_id=>$cell){
				if (is_array($cell)){
					$T->TD($cell['content'],$cell['params']);
				} else {
					$T->TD($cell);
				}
			}
		}
		return $T->getHTML();
	}
	

	
	
	function __closeTR(){
		if ($this->insideRow) {
			if ($this->TRcontent[$this->TRows]['cells']>$this->maxCells) $this->maxCells=$this->TRcontent[$this->TRows]['cells'];
			$this->insideRow=false;
			$this->TRows++;
		}
	}
	
	function caption($text){
		if (!empty($text)){
			$this->HTMLcontent .= '<caption>'.$text.'</caption>';
		}
        return $this;
	}
	
	function Col($content=''){
		$this->Cols[]=trim($content);
        return $this;
	}
	
	function Colgroup($content=''){
		$this->Colgroups[]=trim($content);
        return $this;
	}
	
	
	
	function &TR($params='',$id='',$wrappers=''){
		$this->__closeTR();		
		$this->TRcontent[$this->TRows]=array('id'=>trim($id),'params'=>trim($params),'cells'=>0,'content'=>array(),'wrappers'=>$wrappers);
		$this->insideRow=true;
		return $this;
	}
	
	function &THEAD(){
		$this->TR('','','head');
		return $this;
	}
	
	
	function TD($content='',$params='',$id='',$colspan=0){
		if (!$this->insideRow) $this->TR();
		if (is_array($content)) $content=implode($this->newline.$this->tab.$this->tab,$content);
		$this->TRcontent[$this->TRows]['content'][]=array('id'=>trim($id),'params'=>trim($params),'content'=>trim($content),'colspan'=>$colspan);
		$this->TRcontent[$this->TRows]['cells']++;
        return $this;
	}

    function TRAddClass($class){
        if (!$this->insideRow) return trigger_error('Not in TR now');

        $this->TRcontent[$this->TRows]['params']=$this->addClass($this->TRcontent[$this->TRows]['params'],$class);
        return $this;
    }

    protected function addClass($htmlString = '', $newClass) {
        $newClass=trim($newClass);
        $pattern = '/class="([^"]*)"/';

        // class attribute set
        if (preg_match($pattern, $htmlString, $matches)) {
            $definedClasses = explode(' ', $matches[1]);
            if (!in_array($newClass, $definedClasses)) {
                $definedClasses[] = $newClass;
                $htmlString = str_replace($matches[0], sprintf('class="%s"', implode(' ', $definedClasses)), $htmlString);
            }
        }

        // class attribute not set
        else {
            $htmlString = $htmlString.=' '.sprintf('class="%s" ', $newClass);
        }

        return $htmlString;
    }
	
	function TRTD($content='',$params='',$id=''){
		$this->TR();
		return $this->TD($content,$params,$id);
	}
	
	function TH($content='',$params='',$id='',$colspan=0){
		if (!$this->insideRow) $this->TR();
		if (is_array($content)) $content=implode($this->newline.$this->tab.$this->tab,$content);
		$this->TRcontent[$this->TRows]['content'][]=array('id'=>trim($id),'params'=>trim($params),'content'=>trim($content),'th'=>true,'colspan'=>$colspan);
		$this->TRcontent[$this->TRows]['cells']++;
        return $this;
	}
	
	function getHTML(){
		$this->__closeTR();
		if ($this->maxCells==0) return '';
		foreach ($this->Cols as $col) {
			$this->HTMLcontent.='<col'.($col!='' ? (' '.$col) : '').' />'.$this->newline;
		}
		
		foreach ($this->Colgroups as $col) {
			$this->HTMLcontent.='<colgroup'.($col!='' ? (' '.$col) : '').' ></colgroup>'.$this->newline;
		}
		$this->HTMLcontent.=$this->newline;
        $inhead=false;
		foreach ($this->TRcontent as $row){
			$this->HTMLcontent.=$this->tab;
			if ($row['wrappers']=='head') {
                if (!$inhead){
                    $this->HTMLcontent.='<thead>';
                    $inhead=true;
                }
            } elseif ($inhead){
                $this->HTMLcontent.='</thead>';
                $inhead=false;
            }
			if ($row['wrappers']=='body') $this->HTMLcontent.='<tbody>';;
			
			$this->HTMLcontent.='<tr';
			if ($row['id']!='') $this->HTMLcontent.=' id="'.$row['id'].'"';
			if ($row['params']!='') $this->HTMLcontent.=' '.$row['params'].'';
			$this->HTMLcontent.='>'.$this->newline;

			if ($row['cells']>0) {
				foreach ($row['content'] as $num=>$cell){
					
					$this->HTMLcontent.=$this->tab.$this->tab.(isset($cell['th'])?'<th':'<td');
					if ($cell['id']!='') $this->HTMLcontent.=' id="'.$cell['id'].'"';
					if ($cell['params']!='') $this->HTMLcontent.=' '.$cell['params'].'';
                    if (isset($cell['colspan']) && $cell['colspan']>1){
                        $this->HTMLcontent .= ' colspan="' . $cell['colspan'] . '"';
                    } elseif ($this->autocolspan && $row['cells']<$this->maxCells && ($num+1)==$row['cells']){
						$this->HTMLcontent.=' colspan="'.($this->maxCells-$num).'"';
					}
                    if ($this->nohtml){
                        $cell['content']=strip_tags($cell['content'],'<A><strong>');
                    }
					$this->HTMLcontent.='>'.$cell['content'].(isset($cell['th'])?'</th>':'</td>').$this->newline;
				}
			} else {
				if ($this->autocolspan) {
					$this->HTMLcontent.='<td colspan="'.$this->maxCells.'">'.$this->emptycell.'</td>';
				} else {
					$this->HTMLcontent.='<td>'.$this->emptycell.'</td>';
				}
			}
			$this->HTMLcontent.=$this->tab.'</tr>';
			if ($row['wrappers']=='body') $this->HTMLcontent.='</tbody>';

			
			$this->HTMLcontent.=$this->newline;
		}
        if ($inhead) $this->HTMLcontent.='</thead>';
		$this->HTMLcontent.='</table>'.$this->newline;
		return $this->HTMLcontent;
    }

    function getArray(){
        $this->__closeTR();
        if ($this->maxCells==0) return '';

        $data=array();

        foreach ($this->TRcontent as $row){




            if ($row['cells']>0) {
                $datarow=array();
                foreach ($row['content'] as $num=>$cell){

                    $datarow[]=decode_htmlspecialchars(remove_html($cell['content']));

                    $empty=0;
                    if (isset($cell['colspan']) && $cell['colspan']>1){
                        $empty=sizeof($cell['colspan'])-1;
                    } elseif ($this->autocolspan && $row['cells']<$this->maxCells && ($num+1)==$row['cells']){
                        $empty=$this->maxCells-$num-1;
                    }
                    if ($empty>0){
                        for ($a=0;$a<$empty;$a++){
                            $datarow[]='';
                        }
                    }
                }
            } elseif ($this->autocolspan) {
                $datarow=array_fill (0,$this->maxCells,'');
            }
            $data[]=$datarow;
        }

        return $data;
   }

}
