<?php


if (strcmp(txpinterface,'admin') == 0) {

    register_callback(
    	'kuo_star_rating_enabled',
    	'plugin_lifecycle.kuo_star_rating',
    	'enabled'
    );
    
    register_callback(
    	'kuo_star_rating_deleted',
    	'plugin_lifecycle.kuo_star_rating',
    	'deleted'
    );

    # BEGINS: FUNCTION: ENABLING
    function kuo_star_rating_enabled() {
    
        safe_create(
        	'kuo_star_rating',
        	'
			`row_id` INT(10) NOT NULL AUTO_INCREMENT,
			`article_id` INT(10),
			`stars_val` TINYINT(1) NOT NULL DEFAULT 1,
			`voter_id` VARBINARY(16),
			`timestamp` TIMESTAMP,
			PRIMARY KEY (row_id)
        	'
        );
    }
    # ENDS: FUNCTION: ENABLING

    # BEGINS: FUNCTION: DELETING
    function kuo_star_rating_deleted() {
    
        safe_drop('kuo_star_rating');
    }
    # ENDS: FUNCTION: DELETING

}
# ENDS: ADMIN
# BEGINS: PUBLIC
elseif (strcmp(txpinterface,'public') == 0) {

    Txp::get('\Textpattern\Tag\Registry')->
    	register('kuo_star_rating')->
    		register('kuo_star_rating_snippet');

    # BEGINS: FUNCTION: CREATE STAR RATING TAG (HTML)
    function kuo_star_rating($attributes) {
    
        extract(
        	lAtts(
        		array(
        			'id'=>NULL
        		),
        		$attributes
        	)
        );
        
        if (is_numeric($id)) {
        
            return '<ins class="kuo-star-rating" id="kuo-star-rating-'.
            	intval($id).'"></ins>';
        }
        else {
        
            return '';
        }
    }
    # ENDS: FUNCTION: CREATE STAR RATING TAG (HTML)

    # BEGINS: FUNCTION: CREATE RICH SNIPPET (HTML)
    function kuo_star_rating_snippet($attributes) {
    
        extract(
        	lAtts(
        	filter_var_array(
        		array(
            		'id'=>NULL,
            		'wraptag'=>'p',
            		'wrapclass'=>'kuo-star-rating-snippet',
            		'schema_name'=>'CreativeWork',
            		'decimal_point'=>'.',
            		'lan_is_rated'=>'is rated',
            		'lan_out_of'=>'/',
            		'lan_based_on'=>'based on',
            		'lan_vote'=>'vote',
            		'lan_votes'=>'votes',
        		),
        		FILTER_SANITIZE_STRING
        		),
        		$attributes
        	)
    	);

        if (is_numeric($id)) {

            $kuo_rating_snippet = safe_query(
				'
				SELECT
					AVG('.safe_pfx('kuo_star_rating').'.`stars_val`)
						AS `rating`,
					COUNT('.safe_pfx('kuo_star_rating').'.`row_id`)
						AS `voters`,
					'.safe_pfx('textpattern').'.`Title`
						AS `title`
				FROM
					`'.safe_pfx('kuo_star_rating').'`
				LEFT JOIN
					`'.safe_pfx('textpattern').'`
				ON
					'.safe_pfx('kuo_star_rating').'.`article_id` = '.
						safe_pfx('textpattern').'.`ID`
				WHERE
					'.safe_pfx('kuo_star_rating').'.`article_id` = '.
						intval($id).'
				',
				0
            );

            # BEGINS: SOME RESULTS FOUND
            if (
            	($kuo_rating_snippet)
            	&&
            	(numRows($kuo_rating_snippet) == 1)
            ) {

                $kuo_rating_snippet = nextRow($kuo_rating_snippet);

                # BEGINS: NEEDS AT LEAST ONE VOTE
                if (!empty($kuo_rating_snippet['voters'])) {

                    # MORE THAN ONE VOTE
                    if ($kuo_rating_snippet['voters'] > 1) {
                    
                        $kuo_lan_vote_or_votes = $lan_votes;
                    }
                    # NO VOTES OR ONE VOTE
                    else {
                    
                        $kuo_lan_vote_or_votes = $lan_vote;
                    }

                    if (
                    	(isset($kuo_rating_snippet['title']))
                    	&&
                    	(!empty($kuo_rating_snippet['title']))
                    ) {
	
						$kuo_rating_snippet['title'] =
							htmlentities($kuo_rating_snippet['title']);
                    
                        $kuo_title = '&quot;';
                        $kuo_title .= '<span ';
                        $kuo_title .= 'itemprop="itemReviewed" ';
						$kuo_title .= 'itemscope ';
						$kuo_title .= 'itemtype="https://schema.org/'.
							$schema_name.'">';
						$kuo_title .= '<span itemprop="name">';
						$kuo_title .= $kuo_rating_snippet['title'];
						$kuo_title .= '</span>';
						$kuo_title .= '</span>';
						$kuo_title .= '&quot;';
						$kuo_title .= ' ';
						$kuo_title .= $lan_is_rated;
                    }
                    else {
                    
                        $kuo_title = '';
                    }
	
					$kuo_rating_snippet['rating'] = round(
						$kuo_rating_snippet['rating'],
						2
					);
                    
                    $result = '<'.$wraptag.' class="'.$wrapclass.'" ';
					$result .= 'itemscope ';
					$result .= 'itemtype="https://schema.org/AggregateRating">';
					$result .= '<meta ';
					$result .= 'itemprop="worstRating" ';
					$result .= 'content="1">';
					$result .= '<meta ';
					$result .= 'itemprop="bestRating" ';
					$result .= 'content="5">';
					$result .= '<meta ';
					$result .= 'itemprop="ratingValue" ';
					$result .= 'content="'.$kuo_rating_snippet['rating'].'">';
					$result .= '<meta ';
					$result .= 'itemprop="ratingCount" ';
					$result .= 'content="'.$kuo_rating_snippet['voters'].'">';
					
					$result .= $kuo_title;
					$result .= ' ';
	
					$kuo_rating_snippet['rating'] = str_replace(
						'.',
						$decimal_point,
						$kuo_rating_snippet['rating']
					);
					
					$result .= $kuo_rating_snippet['rating'];
					$result .= ' ';
					$result .= $lan_out_of;
					$result .= ' 5, ';
					$result .= $lan_based_on;
					$result .= ' ';
					$result .= $kuo_rating_snippet['voters'];
					$result .= ' ';
					$result .= $kuo_lan_vote_or_votes;
					$result .= '.';
					$result .= '</'.$wraptag.'>';
	
					return $result;
                }
                # ENDS: NEEDS AT LEAST ONE VOTE
            }
            # ENDS: SOME RESULTS FOUND
        }
        else {
        
            return '';
        }
    }
    # ENDS: FUNCTION: CREATE RICH SNIPPET (HTML)
}
# ENDS: PUBLIC