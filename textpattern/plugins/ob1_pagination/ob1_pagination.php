<?php
	/*
		Plugin: ob1_pagination
		URL: http://rise.lewander.com/textpattern/ob1-pagination
		Released under the Creative Commons Attribution-Share Alike 3.0 Unported, http://creativecommons.org/licenses/by-sa/3.0/
	*/

	function ob1_pagination($atts) {
		global $thispage,$q,$prefs,$pretext;
		extract($pretext);
		if(is_array($thispage)) extract($thispage);
		extract($prefs);
		if(!isset($pg)) $pg=1;
		$numberOfTabs = (empty($thispage)) ? 1: $numPages;

		extract(lAtts(array(
			"maximumtabs"=>"11", # maximum number of tabs displayed
			"firsttext"=>"&#171;",
			"firsttexttitle"=>"First",
			"previoustext"=>"&#8249;",
			"previoustexttitle"=>"Previous",
			"lasttext"=>"&#187;",
			"lasttexttitle"=>"Last",
			"nexttext"=>"&#8250;",
			"nexttexttitle"=>"Next",
			"pagetext"=>"Page",
			"ulid"=>'',
			"ulclass"=>'',
			"liclass"=>'', # default class, added to every <li>
			"liselected"=>'', # selected class, added to the current tab
			"liselectedtext"=>'', # if you want the selected <li> to contain something else then the number, add it here
			"liempty"=>'', # empty class, added to the <li> that do not hold an anchor <a>
			"linkcurrent"=>"0",
			"outputlastfirst"=>"1",
			"outputnextprevious"=>"1",
			"reversenumberorder"=>"0", # want the numbers reversed? no sweat. change to 1
			"moretabsdisplay"=>"", # may contain before or after or both if they're comma-separated
			"moretabstext"=>"...",
			"wraptag"=>"",
		),$atts));

		$ulid=(empty($ulid))?'':' id="'.$ulid.'"';
		$ulclass=(empty($ulclass))?'':' class="'.$ulclass.'"';

		$addToURL = ($permlink_mode=='messy') ? "&amp;s=$s" : "" ;
		$addToURL .= ($c) ? "&amp;c=$c" : "";

		# if we got tabs, start the outputting
		if($numberOfTabs>1){
			if($maximumtabs==1) $maximumtabs=11; # using just one tab is folly! folly i say

			# this is for the search
			if($q and (is_callable(@ob1_advanced_search))){
				# if you're using ob1_advanced_search [v1.0b and above], add some stuff to the URL
				$ob1ASGet = array('rpp','wh','ww','oc','ad','sd','ed','bts');
				foreach($ob1ASGet as $val) {
					$$val = (!empty($_GET[$val])) ? "&amp;$val=".urlencode($_GET[$val]) : '';
				}
				$addToURL .= "&amp;q=".urlencode($q).$rpp.$wh.$ww.$oc.$ad.$sd.$ed.$bts;
				unset($ob1ASGet,$rpp,$wh,$ww,$oc,$ad,$sd,$ed,$bts);
			}elseif($q){
				$addToURL .= "&amp;q=".urlencode($q);
			}

			if($numberOfTabs>$maximumtabs){
				$loopStart = $pg-floor($maximumtabs/2);
				$loopEnd = $loopStart+$maximumtabs;
				if($loopStart<1){
					$loopStart = 1;
					$loopEnd = $maximumtabs+1;
				}
				if($loopEnd>$numberOfTabs){
					$loopEnd = $numberOfTabs+1;
					$loopStart = $loopEnd - $maximumtabs;
					if($loopStart<1) $loopStart = 1;
				}
			}else{
				$loopStart = 1;
				$loopEnd = $maximumtabs+1;
			}
			if($loopEnd>$numberOfTabs){
				$loopEnd = $numberOfTabs+1;
			}
			$out=array();
			if($pg>1){
				$out[] = ($outputlastfirst) ? "<li class='$liclass'><a href='?pg=1$addToURL' title='".$firsttexttitle."'>".$firsttext."</a></li>".n : "";
				$out[] = ($outputnextprevious) ? "<li class='$liclass'><a href='?pg=".($pg-1)."$addToURL' title='".$previoustexttitle."'>".$previoustext."</a></li>".n : "";
			}else{
				$out[] = ($outputlastfirst) ? "<li class='$liempty $liclass'>".$firsttext."</li>".n : "";
				$out[] = ($outputnextprevious) ? "<li class='$liempty $liclass'>".$previoustext."</li>".n : "";
			}

			if(in_list("before",$moretabsdisplay) and $loopStart>1){
				$out[] = "<li class='$liclass $liempty'>".$moretabstext."</li>";
			}

			for($i=$loopStart;$i<$loopEnd;$i++){
				if($i==$pg){
					$out[] = "<li class='$liselected $liclass";
					$out[] = ($linkcurrent) ? "'>" : " $liempty'>";
					$out[] = ($linkcurrent) ? "<a href='?pg=".$i."$addToURL' title='".$pagetext : "";
					if($reversenumberorder){
						$out[] = ($linkcurrent) ? " ".($numberOfTabs-$i+1)."'>" : '';
						$out[] = ($liselectedtext) ? $liselectedtext : ($numberOfTabs-$i+1);
					}else{
						$out[] = ($linkcurrent) ? " ".$i."'>" : '';
						$out[] = ($liselectedtext) ? $liselectedtext : $i;
					}
					$out[] = ($linkcurrent) ? "</a>" : "";
					$out[] = "</li>".n;
				}else{
					$out[] = "<li class='$liclass'><a href='?pg=".$i."$addToURL' title='".$pagetext;
					$out[] = ($reversenumberorder) ? " ".($numberOfTabs-$i+1)."'>".($numberOfTabs-$i+1) : " ".$i."'>".$i ;
					$out[] = "</a></li>".n;
				}
			}

			if(in_list("after",$moretabsdisplay) and $loopEnd<=$numberOfTabs){
				$out[] = "<li class='$liclass $liempty'>".$moretabstext."</li>";
			}

			if($pg==$numberOfTabs){
				$out[] = ($outputnextprevious) ? "<li class='$liempty $liclass'>".$nexttext."</li>".n : "";
				$out[] = ($outputlastfirst) ? "<li class='$liempty $liclass'>".$lasttext."</li>".n : "";
			}else{
				$out[] = ($outputnextprevious) ? "<li class='$liclass'><a href='?pg=".($pg+1)."$addToURL' title='".$nexttexttitle."'>".$nexttext."</a></li>".n : "";
				$out[] = ($outputlastfirst) ? "<li class='$liclass'><a href='?pg=".$numberOfTabs."$addToURL' title='".$lasttexttitle."'>".$lasttext."</a></li>".n : "";
			}
			return ($wraptag) ? tag("<ul".$ulclass.$ulid.">".n.join("", $out)."</ul>",$wraptag) : "<ul".$ulclass.$ulid.">".n.join("", $out)."</ul>";
		}else{
			return false;
		}
	} # let's end it here and now