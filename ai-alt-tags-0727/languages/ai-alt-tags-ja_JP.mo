Þ    -        =   ì      à  É   á     «  5  ·     í     õ  g     :   i     ¤     ²     Á     Ù     ó                9     N  D   V       {   ¢  _   	     ~	  
   	     	     	      	     ©	  *   É	     ô	     
     )
  8   ?
     x
     
  T   
  o   ï
     _     o  n   w  z   æ     a     n       *        Ê  B  è  &  +     R  ß  ^  	   >     H  o   [  <   Ë            !   $     F     \  6   i  $      '   Å     í  n   ô     c     s     û     }       	      	   ª     ´  0   »  <   ì  '   )  $   Q     v  n        ø       t   !  Ã     !   Z     |  x          	          0   ³  <   ä  '   !         $          ,       !       -            "                            *   +   %   
                                         (                	           )                   #   &                       '        A WordPress plugin that automatically generates image alt tags using Azure's Computer Vision API for better accessibility and SEO. Batch process all posts, with WP CLI integration for faster execution. AI Alt Tags AI Alt Tags is an advanced feature integrated into the BoardingPack plugin that enhances website accessibility and SEO by automatically generating image alt tags using Microsoft Azure's Computer Vision API. Streamlining the process of adding descriptive alt tags, this feature supports batch processing of all posts and offers WP CLI integration for faster execution, ensuring content is accessible and optimized for search engines while intelligently analyzing images to generate accurate, meaningful alt tags that contribute to your website's overall performance. API Key API Version Adds alt tags to images that don't have them or have outdated ones. Existing alt tags remain unchanged. Automatically generate alt tags on post save (recommended) Azure API Key Azure Settings Batch Process All Posts Batch Processing Settings BoardingArea DO NOT CLOSE THIS WINDOW. Default Behavior (recommended) Dismiss this notice. English Enter the API Key provided by Azure for the Computer Vision service. French Generates alt tags only for images that currently have empty alt tags during batch processing, regardless of their version. Generates new alt tags for all images during batch processing, replacing any existing alt tags. German Indonesian Japanese Korean Language Overwrite all existing alt tags Overwrite alt tags from a specific version Overwrite empty alt tags only Portuguese (Brazilian) Post Editing Settings Pro users: For faster batching, use the WP CLI commands: Processing alt tags Save Settings Select the API version to use for generating alt tags. Choose between v3.2 and v4.0. Select the language for the generated alt text. Note that not all languages are supported by both API versions. Settings saved. Spanish Updates alt tags only for images that were generated by a specific version of the API during batch processing. When enabled, the feature will automatically add or update alt tags for images in a post when you save or update the post. completed... default behavior overwrite all existing alt tags overwrite alt tags from a specific version overwrite empty alt tags only Project-Id-Version: AI Alt Tags 0613 1.0.0
Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/ai-alt-tags-0607
PO-Revision-Date: 2023-06-13 21:23-0600
Last-Translator: 
Language-Team: 
Language: ja
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
X-Generator: Poedit 3.3.1
 Azureã®Computer Vision APIãä½¿ç¨ãã¦ç»åã®altã¿ã°ãèªåçæããWordPressãã©ã°ã¤ã³ã§ãããããã¢ã¯ã»ã·ããªãã£ã¨SEOãå®ç¾ãã¾ããå¨æç¨¿ããããå¦çããããéãå®è¡ããããã«WP CLIã¤ã³ãã°ã¬ã¼ã·ã§ã³ãæä¾ãã¾ãã AI Alt Tags AI Alt Tagsã¯ãBoardingPackãã©ã°ã¤ã³ã«çµ±åãããé²åããæ©è½ã§ãMicrosoft Azureã®Computer Vision APIãä½¿ç¨ãã¦ç»åã®altã¿ã°ãèªåçæãããã¨ã§ãã¦ã§ããµã¤ãã®ã¢ã¯ã»ã·ããªãã£ã¨SEOãå¼·åãã¾ããèª¬æçãªaltã¿ã°ãè¿½å ãããã­ã»ã¹ãåçåããå¨æç¨¿ã®ãããå¦çãæ¯æ´ããWP CLIã¤ã³ãã°ã¬ã¼ã·ã§ã³ã®æä¾ã«ãããããè¿éãªå®è¡ãå¯è½ã«ãããã¨ã§ãã³ã³ãã³ããã¢ã¯ã»ã·ãã«ã§æ¤ç´¢ã¨ã³ã¸ã³ã«æé©åããããã¨ãä¿è¨¼ããç»åãç¥çã«åæãã¦æ­£ç¢ºã§æå³ã®ããaltã¿ã°ãçæãããªãã®ã¦ã§ããµã¤ãã®å¨ä½çãªããã©ã¼ãã³ã¹ã«è²¢ç®ãã¾ãã APIã­ã¼ APIãã¼ã¸ã§ã³ altã¿ã°ããªããå¤ãç»åã«altã¿ã°ãè¿½å ãã¾ããæ¢å­ã®altã¿ã°ã¯å¤æ´ããã¾ããã æç¨¿ä¿å­æã«altã¿ã°ãèªåçæããï¼æ¨å¥¨ï¼ Azure APIã­ã¼ Azureè¨­å® å¨æç¨¿ããããå¦çãã ãããå¦çè¨­å® BoardingArea ãã®ã¦ã£ã³ãã¦ãéããªãã§ãã ããã ããã©ã«ãã®æåï¼æ¨å¥¨ï¼ ãã®éç¥ãéãã¦ãã ããã è±èª Computer Visionãµã¼ãã¹ã®ããã«Azureã«ãã£ã¦æä¾ãããAPIã­ã¼ãå¥åãã¦ãã ããã ãã©ã³ã¹èª ãããå¦çä¸­ã«ç¾å¨ç©ºã®altã¿ã°ãæã¤ç»åã®altã¿ã°ã®ã¿ãçæãã¾ãããã®ãã¼ã¸ã§ã³ã«é¢ãããã ãããå¦çä¸­ã«ãã¹ã¦ã®ç»åã®altã¿ã°ãæ°ããçæãããã§ã«å­å¨ããaltã¿ã°ãç½®ãæãã¾ãã ãã¤ãèª ã¤ã³ããã·ã¢èª æ¥æ¬èª éå½èª è¨èª ãã¹ã¦ã®æ¢å­ã®altã¿ã°ãä¸æ¸ããã ç¹å®ã®ãã¼ã¸ã§ã³ããã®altã¿ã°ãä¸æ¸ããã ç©ºã®altã¿ã°ã®ã¿ãä¸æ¸ããã ãã«ãã¬ã«èªï¼ãã©ã¸ã«ï¼ æç¨¿ç·¨éè¨­å® ãã­ã¦ã¼ã¶ã¼ã¸:ããéããããå¦çã®ããã«ãWP CLIã³ãã³ããä½¿ç¨ãã¦ãã ãã: altã¿ã°ã®å¦ç è¨­å®ãä¿å­ãã altã¿ã°çæã«ä½¿ç¨ããAPIãã¼ã¸ã§ã³ãé¸æãã¾ããv3.2ã¨v4.0ã®éã§é¸æãã¦ãã ããã çæãããaltãã­ã¹ãã®è¨èªãé¸æãã¾ããä¸¡æ¹ã®APIãã¼ã¸ã§ã³ã§ã¯ãã¹ã¦ã®è¨èªããµãã¼ãããã¦ããããã§ã¯ãªããã¨ã«æ³¨æãã¦ãã ããã è¨­å®ãä¿å­ããã¾ããã ã¹ãã¤ã³èª ãããå¦çä¸­ã«ç¹å®ã®APIãã¼ã¸ã§ã³ã«ãã£ã¦çæãããç»åã®altã¿ã°ã®ã¿ãæ´æ°ãã¾ãã æå¹ããã¨ãæç¨¿ãä¿å­ã¾ãã¯æ´æ°ããã¨ãã«ã¤ã¡ã¼ã¸ã®altã¿ã°ãèªåçã«è¿½å ã¾ãã¯æ´æ°ããã¾ãã å®äº... ããã©ã«ãã®æå ãã¹ã¦ã®æ¢å­ã®altã¿ã°ãä¸æ¸ããã ç¹å®ã®ãã¼ã¸ã§ã³ããã®altã¿ã°ãä¸æ¸ããã ç©ºã®altã¿ã°ã®ã¿ãä¸æ¸ããã 