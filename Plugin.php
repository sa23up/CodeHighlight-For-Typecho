<?php
/**
 * 代码高亮插件，支持显示行号、代码复制、语言类型显示
 * 
 * @package CodeHighlight
 * @author shiya
 * @version 1.0.0
 * @link https://github.com
 */
class CodeHighlight_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->header = array('CodeHighlight_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('CodeHighlight_Plugin', 'footer');
        // 添加内容解析钩子
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('CodeHighlight_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('CodeHighlight_Plugin', 'parse');
        return _t('插件已启用');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        return _t('插件已禁用');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {        $theme = new Typecho_Widget_Helper_Form_Element_Select(
            'theme',            array(
                'default' => _t('默认主题'),
                'dark' => _t('暗色主题'),
                'coy' => _t('Coy主题'),
                'okaidia' => _t('Okaidia主题'),
                'twilight' => _t('Twilight主题'),
                'solarizedlight' => _t('Solarized Light主题'),
                'tomorrow' => _t('Tomorrow主题'),
                'material' => _t('Material主题'),
                'one-dark' => _t('One Dark主题')
            ),
            'default',
            _t('代码高亮主题'),
            _t('选择代码高亮的主题风格')
        );
        $form->addInput($theme);
        
        $showLineNumbers = new Typecho_Widget_Helper_Form_Element_Radio(
            'showLineNumbers',
            array('1' => _t('是'), '0' => _t('否')),
            '1',
            _t('显示行号'),
            _t('是否显示代码行号')
        );
        $form->addInput($showLineNumbers);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }
    
    /**
     * 输出头部CSS
     * 
     * @access public
     * @return void
     */
    public static function header()
    {
        $options = Helper::options();
        $theme = $options->plugin('CodeHighlight')->theme;
        echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism.min.css">';
        if ($theme != 'default') {
            echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-' . $theme . '.min.css">';
        }
        echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/line-numbers/prism-line-numbers.css">';        echo '<style>
            pre {
                position: relative;
                padding-top: 2.5em !important;
                margin: 0.5em 0;
            }
            .copy-button {
                position: absolute;
                top: 0.5em;
                right: 0.5em;
                padding: 5px 10px;
                background: #f5f5f5;
                border: 1px solid #ccc;
                border-radius: 3px;
                cursor: pointer;
                font-size: 12px;
                z-index: 100;
            }
            .copy-button:hover {
                background: #e5e5e5;
            }
            .code-toolbar {
                position: relative;
            }
            .language-mark {
                position: absolute;
                top: 0.5em;
                left: 0.5em;
                padding: 2px 5px;
                background: #f5f5f5;
                border: 1px solid #ccc;
                border-radius: 3px;
                font-size: 12px;
                z-index: 100;
            }
            /* 确保代码块内容不会遮挡按钮 */
            pre code {
                display: block;
                margin-top: 0;
            }
            /* 优化行号显示 */
            pre.line-numbers {
                padding-left: 3.8em;
                padding-top: 2.5em;
            }
            .line-numbers .line-numbers-rows {
                top: 2.5em;
            }
        </style>';
    }
    
    /**
     * 输出尾部JavaScript
     * 
     * @access public
     * @return void
     */
    public static function footer()
    {
        $options = Helper::options();
        $showLineNumbers = $options->plugin('CodeHighlight')->showLineNumbers;
        
        echo '<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>';
        if ($showLineNumbers) {
            echo '<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>';
        }
        
        // 加载常用的语言支持
        $languages = array('markup', 'css', 'clike', 'javascript', 'php', 'python', 'java', 'bash', 'sql');
        foreach ($languages as $lang) {
            echo '<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-' . $lang . '.min.js"></script>';
        }
        
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // 为所有代码块添加复制按钮和语言标记
            document.querySelectorAll("pre code").forEach(function(block) {
                var pre = block.parentNode;
                
                // 添加line-numbers类
                if (' . $showLineNumbers . ') {
                    pre.classList.add("line-numbers");
                }
                      // 获取语言类名
                var language = "";
                Array.from(block.classList).forEach(function(className) {
                    if (className.startsWith("language-")) {
                        language = className.replace("language-", "");
                    }
                });
                
                // 添加语言标记
                if (language) {
                    var languageMark = document.createElement("span");
                    languageMark.className = "language-mark";
                    languageMark.textContent = language;
                    pre.appendChild(languageMark);
                }
                
                // 添加复制按钮
                var button = document.createElement("button");
                button.className = "copy-button";
                button.textContent = "复制代码";
                
                button.addEventListener("click", function() {
                    var code = block.textContent;
                    navigator.clipboard.writeText(code).then(function() {
                        button.textContent = "已复制！";
                        setTimeout(function() {
                            button.textContent = "复制代码";
                        }, 2000);
                    }).catch(function(err) {
                        console.error("无法复制代码: ", err);
                        button.textContent = "复制失败";
                    });
                });
                
                pre.appendChild(button);
            });
            
            // 重新执行Prism.js的高亮
            Prism.highlightAll();
        });
        </script>';
    }
    
    /**
     * 解析内容
     * 
     * @access public
     * @param string $text 文章内容
     * @param Widget_Abstract_Contents $widget 文章组件
     * @return string
     */
    public static function parse($text, $widget)
    {
        // 匹配 ```language\n code ``` 形式的代码块
        $text = preg_replace_callback('/```([\w-]+)?\n(.*?)\n```/s', function($matches) {
            $language = empty($matches[1]) ? '' : $matches[1];
            $code = trim($matches[2]);
            
            // 转义HTML特殊字符
            $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
            
            // 生成代码块HTML
            return sprintf(
                '<pre class="line-numbers"><code class="language-%s">%s</code></pre>',
                htmlspecialchars($language, ENT_QUOTES, 'UTF-8'),
                $code
            );
        }, $text);

        // 匹配单行代码 `code` 的情况
        $text = preg_replace_callback('/`(.*?)`/', function($matches) {
            $code = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
            return sprintf('<code class="language-none">%s</code>', $code);
        }, $text);

        return $text;
    }
}
