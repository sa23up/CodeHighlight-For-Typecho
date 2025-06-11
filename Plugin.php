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
        echo '<link href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism.min.css" rel="stylesheet">';
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
        echo '<style>
        .code-container {
            margin: 40px 0;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid #e1e4e8;
        }
        
        .code-container h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.8rem;
            text-align: center;
        }
        
        /* 代码块样式 - 核心修改部分 */
        pre {
            position: relative;
            padding: 1.5em 1.5em 1.5em 4em !important; /* 左边距加大给行号留空间 */
            margin: 1.5em 0;
            border-radius: 8px !important;
            background: #f8fafc !important;
            border: 1px solid #e1e4e8;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            z-index: 0;
        }
        
        pre:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #d1d5da;
        }
        
        pre code {
            display: block;
            padding: 0 !important;
            overflow-x: auto;
            font-size: 16px;
            line-height: 1.5;
            color: #333;
            background: transparent !important;
            border-radius: 4px;
        }
        
        /* 语言标记和复制按钮容器 - 修改为固定定位 */
        .code-header {
            position: sticky;
            top: 0;
            right: 0;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 100;
            flex-direction: row-reverse; /* 反转顺序使复制按钮在左侧 */
            margin-bottom: calc(-1 * (10px + 15px + 2em)); /* 动态计算负边距 */
        }
        
        /* 语言标记 */
        .language-mark {
            padding: 5px 12px;
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: 1px solid rgba(52, 152, 219, 0.2);
            white-space: nowrap;
        }
        
        /* 复制按钮 */
        .copy-button {
            padding: 8px 15px;
            background: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.2);
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            opacity: 0;
            transform: translateY(-5px);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        pre:hover .copy-button {
            opacity: 1;
            transform: translateY(0);
        }
        
        .copy-button:hover {
            background: rgba(46, 204, 113, 0.2);
            color: #27ae60;
            border-color: #2ecc71;
        }
        
        .copy-button:active {
            transform: scale(0.95);
        }
        
        .copy-button i {
            font-size: 16px;
        }
        
        .code-toolbar {
            position: relative;
        }
        
        /* 行号样式 - 修改部分 */
        pre.line-numbers {
            position: relative;
            counter-reset: linenumber;
        }
        
        .line-numbers .line-numbers-rows {
            width: 100% !important; /* 覆盖整行宽度 */
            left: 0;
            position: absolute;
            top: 1.5em; /* 与pre的padding-top一致 */
        }
        
        /* 用伪元素创建整行背景 */
        .line-numbers .line-numbers-rows > span {
            padding-left: 3.5em; /* 给行号留空间 */
            position: relative;
            display: block;
            counter-increment: linenumber;
            height: 1.5em; /* 与行高一致 */
            line-height: 1.5em; /* 垂直居中 */
            text-align: center; /* 水平居中 */
        }
        
        /* 用伪元素创建行号边条 */
        .line-numbers .line-numbers-rows > span:before {
            content: counter(linenumber);
            color: #7f8c8d;
            position: absolute;
            left: 0;
            text-align: center;
            width: 3.5em;
            background: #f1f5f9;
            border-right: 1px solid #e1e4e8;
        }

        /* 保持行号位置 */
        .line-numbers .line-numbers-rows > span::after {
            content: " 1";
            position: absolute;
            left: 0;
            width: 100% !important; /* 覆盖整行宽度 */
            text-align: left;
            z-index: -1;
        }

        /* 交替背景色 */
        .line-numbers .line-numbers-rows > span:nth-child(odd)::after {
            background: #f8fafc;
        }
        .line-numbers .line-numbers-rows > span:nth-child(even)::after {
            background: #f1f5f9;
        }
        
        /* 展开/折叠按钮 */
        .expand-button {
            padding: 8px 15px;
            background: rgba(155, 89, 182, 0.1);
            color: #9b59b6;
            border: 1px solid rgba(155, 89, 182, 0.2);
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            opacity: 0;
            transform: translateY(-5px);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            margin-right: 10px;
        }
        
        pre:hover .expand-button {
            opacity: 1;
            transform: translateY(0);
        }
        
        .expand-button:hover {
            background: rgba(155, 89, 182, 0.2);
            color: #8e44ad;
            border-color: #9b59b6;
        }
        
        .expand-button:active {
            transform: scale(0.95);
        }
        
        .expand-button i {
            font-size: 16px;
        }
        
        /* 折叠状态样式 - 修改为可滚动 */
        pre.collapsed {
            max-height: 31.5em; /* 20行 * 1.5em + 上下padding */
            overflow-y: auto; /* 允许垂直滚动 */
            padding-bottom: 0px !important; /* 为遮罩预留空间 */
        }
        
        /* 展开状态样式 */
        pre.expanded {
            max-height: none;
            overflow-y: auto;
        }
        
        /* 渐变遮罩 - 修改为仅在底部 */
        .fade-mask {
            position: sticky;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to bottom, rgba(248, 250, 252, 0), rgba(248, 250, 252, 1));
            pointer-events: none;
            display: none;
            margin-top: -80px;
        }
        
        pre.collapsed .fade-mask {
            display: block;
        }
        
        /* 滚动条样式 */
        pre::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        
        pre::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        pre::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        pre::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {

            .code-header {
                flex-direction: column; /* 改为垂直排列 */
                align-items: flex-end; /* 右对齐 */
                gap: 8px; /* 减小间距 */
            }

            /* 调整按钮在小屏幕时的样式 */
            .language-mark
            .copy-button, 
            .expand-button {
                padding: 6px 12px; /* 减小按钮内边距 */
                font-size: 13px; /* 减小字体大小 */
            }
            
            .code-container {
                padding: 20px 15px;
            }
            
            /* 移动端显示所有按钮 */
            .copy-button, .expand-button {
                transform: translateY(0);
            }
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
        echo '<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>';
        echo ' <script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>';
        
        // 加载常用的语言支持
        $languages = array('markup', 'css', 'clike', 'javascript', 'python', 'java', 'bash', 'sql');
        foreach ($languages as $lang) {
            echo '<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-' . $lang . '.min.js"></script>';
        }
        
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // 为所有代码块添加复制按钮和语言标记
            document.querySelectorAll("pre").forEach(function(pre) {
                // 创建代码头部容器
                const codeHeader = document.createElement("div");
                codeHeader.className = "code-header";
                
                // 获取语言类名
                let language = "";
                Array.from(pre.classList).forEach(function(className) {
                    if (className.startsWith("language-")) {
                        language = className.replace("language-", "");
                    }
                });
                
                // 添加语言标记
                if (language) {
                    const languageMark = document.createElement("span");
                    languageMark.className = "language-mark";
                    languageMark.textContent = language;
                    codeHeader.appendChild(languageMark);
                }
                
                // 添加复制按钮
                const copyButton = document.createElement("button");
                copyButton.className = "copy-button";
                copyButton.innerHTML = \'<i class="far fa-copy"></i>复制代码\';
                
                copyButton.addEventListener("click", function() {
                    const code = pre.querySelector("code").textContent;
                    navigator.clipboard.writeText(code).then(function() {
                        const originalHTML = copyButton.innerHTML;
                        copyButton.innerHTML = \'<i class="fas fa-check"></i>已复制！\';
                        setTimeout(function() {
                            copyButton.innerHTML = originalHTML;
                        }, 2000);
                    }).catch(function(err) {
                        console.error("无法复制代码: ", err);
                        copyButton.innerHTML = \'<i class="fas fa-times"></i>复制失败\';
                    });
                });
                
                codeHeader.appendChild(copyButton);
                
                // 计算行数
                const code = pre.querySelector("code");
                const lineCount = code.querySelectorAll(\'span\').length;
                
                // 如果超过20行，添加展开/折叠功能
                if (lineCount > 20) {
                    // 添加展开/折叠按钮
                    const expandButton = document.createElement("button");
                    expandButton.className = "expand-button";
                    expandButton.innerHTML = \'<i class="fas fa-chevron-down"></i>展开全部\';
                    
                    // 添加渐变遮罩
                    const fadeMask = document.createElement("div");
                    fadeMask.className = "fade-mask";
                    pre.appendChild(fadeMask);
                    
                    // 初始状态为折叠但可滚动
                    pre.classList.add("collapsed");
                    
                    // 监听滚动事件，当滚动到底部时隐藏遮罩
                    pre.addEventListener(\'scroll\', function() {
                        const scrollBottom = pre.scrollHeight - pre.scrollTop - pre.clientHeight;
                        if (scrollBottom < 10) { // 接近底部
                            fadeMask.style.opacity = \'0\';
                        } else {
                            fadeMask.style.opacity = \'1\';
                        }
                    });
                    
                    expandButton.addEventListener("click", function() {
                        if (pre.classList.contains("collapsed")) {
                            // 展开代码
                            pre.classList.remove("collapsed");
                            pre.classList.add("expanded");
                            expandButton.innerHTML = \'<i class="fas fa-chevron-up"></i>折叠代码\';
                            fadeMask.style.display = \'none\';
                        } else {
                            // 折叠代码
                            pre.scrollTop = 0;
                            pre.classList.remove("expanded");
                            pre.classList.add("collapsed");
                            expandButton.innerHTML = \'<i class="fas fa-chevron-down"></i>展开全部\';
                            fadeMask.style.display = \'block\';
                            fadeMask.style.opacity = \'1\';
                        }
                    });
                    
                    codeHeader.insertBefore(expandButton, copyButton);
                }
                
                // 将代码头部容器插入到pre的最前面
                pre.insertBefore(codeHeader, pre.firstChild);
                
                // 添加行号类
                pre.classList.add("line-numbers");
            });

            // 计算并设置 margin-bottom（包含上下 padding）
            function updateHeaderMargin() {
                document.querySelectorAll(".code-header").forEach(function(header) {
                    const totalHeight = header.offsetHeight;
                    
                    header.style.marginBottom = `-${totalHeight}px`;
                });
            }
            // 初始计算
            updateHeaderMargin();
            // 添加 resize 观察器
            const resizeObserver = new ResizeObserver(updateHeaderMargin);
            document.querySelectorAll(".code-header").forEach(header => {
                resizeObserver.observe(header);
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
