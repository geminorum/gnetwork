/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/index.js":
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

var registerBlockType = wp.blocks.registerBlockType;
var _wp$editor = wp.editor,
    InspectorControls = _wp$editor.InspectorControls,
    BlockControls = _wp$editor.BlockControls,
    AlignmentToolbar = _wp$editor.AlignmentToolbar;
var _wp$components = wp.components,
    ServerSideRender = _wp$components.ServerSideRender,
    TextControl = _wp$components.TextControl,
    ToggleControl = _wp$components.ToggleControl;

var _wp$data$select = wp.data.select('core/editor'),
    getCurrentPostId = _wp$data$select.getCurrentPostId;

var _x = wp.i18n._x;
registerBlockType('gnetwork/post-title', {
  title: _x('Post Title', 'Blocks: Post Title', 'gnetwork'),
  description: _x('Displays selected post title in an HTML tag.', 'Blocks: Post Title', 'gnetwork'),
  icon: 'editor-textcolor',
  category: 'common',
  keywords: [_x('title', 'Blocks', 'gnetwork'), _x('post title', 'Blocks', 'gnetwork'), _x('heading', 'Blocks', 'gnetwork')],
  supports: {
    customClassName: false,
    reusable: false
  },
  attributes: {
    post: {
      default: '',
      type: 'string'
    },
    link: {
      default: true,
      type: 'boolean'
    },
    wrap: {
      default: '',
      type: 'string'
    },
    alignment: {
      default: 'none',
      type: 'string'
    }
  },
  edit: function edit(props) {
    var _props$attributes = props.attributes,
        post = _props$attributes.post,
        link = _props$attributes.link,
        wrap = _props$attributes.wrap,
        alignment = _props$attributes.alignment;
    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])("div", null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(BlockControls, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(AlignmentToolbar, {
      value: alignment,
      onChange: function onChange(value) {
        props.setAttributes({
          alignment: value === undefined ? 'none' : value
        });
      }
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(InspectorControls, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(TextControl, {
      label: _x('Post ID', 'Blocks: Post Title', 'gnetwork'),
      help: _x('Leave empty for current post.', 'Blocks: Post Title', 'gnetwork'),
      className: "gnetwork-component -code",
      value: post,
      onChange: function onChange(value) {
        props.setAttributes({
          post: value
        });
      }
    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(TextControl, {
      label: _x('Wrap Tag', 'Blocks: Post Title', 'gnetwork'),
      help: _x('Use any HTML tags for wrapping.', 'Blocks: Post Title', 'gnetwork'),
      className: "gnetwork-component -code",
      value: wrap,
      onChange: function onChange(value) {
        props.setAttributes({
          wrap: value
        });
      }
    }), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(ToggleControl, {
      label: _x('Link to Post', 'Blocks: Post Title', 'gnetwork'),
      checked: link,
      onChange: function onChange(value) {
        props.setAttributes({
          link: value
        });
      }
    })), Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(ServerSideRender, {
      block: "gnetwork/post-title",
      attributes: props.attributes // className={className} // https://core.trac.wordpress.org/ticket/45882
      ,
      urlQueryArgs: {
        post_id: getCurrentPostId()
      } // https://wordpress.stackexchange.com/a/320681/

    }));
  },
  save: function save(props) {
    return null;
  },
  transforms: {
    from: [{
      type: 'shortcode',
      tag: 'post-title'
    }]
  }
});

/***/ }),

/***/ "@wordpress/element":
/*!******************************************!*\
  !*** external {"this":["wp","element"]} ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = this["wp"]["element"]; }());

/***/ })

/******/ });
//# sourceMappingURL=index.js.map