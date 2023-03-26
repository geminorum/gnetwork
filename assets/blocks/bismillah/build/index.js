/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ (function(module) {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ (function(module) {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ (function(module) {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ (function(module) {

module.exports = window["wp"]["i18n"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
!function() {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__);




(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__.registerBlockType)('gnetwork/bismillah', {
  apiVersion: 2,
  title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__._x)('Bismillah', 'Block: Bismillah', 'gnetwork'),
  description: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__._x)('Displays Bismillah unicode char with styles.', 'Block: Bismillah', 'gnetwork'),
  icon: 'editor-textcolor',
  category: 'layout',
  example: {},

  edit(props) {
    const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.useBlockProps)();
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", blockProps, "\uFDFD");
  },

  save() {
    const blockProps = _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_3__.useBlockProps.save();
    return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", blockProps, "\uFDFD");
  }

}); // const { registerBlockType } = wp.blocks;
// const { InspectorControls, BlockControls, AlignmentToolbar } = wp.editor;
// const { ServerSideRender, TextControl, ToggleControl } = wp.components;
// const { getCurrentPostId } = wp.data.select('core/editor');
// const { _x } = wp.i18n;
//
// registerBlockType('gnetwork/bismillah', {
//   title: _x('Bismillah', 'Block: Bismillah', 'gnetwork'),
//   description: _x('Displays selected post title in an HTML tag.', 'Block: Bismillah', 'gnetwork'),
//   icon: 'editor-textcolor',
//   category: 'common',
//   keywords: [
//     _x('title', 'Blocks', 'gnetwork'),
//     _x('post title', 'Blocks', 'gnetwork'),
//     _x('heading', 'Blocks', 'gnetwork')
//   ],
//   supports: {
//     customClassName: false,
//     reusable: false
//   },
//   attributes: {
//     post: {
//       default: '',
//       type: 'string'
//     },
//     link: {
//       default: true,
//       type: 'boolean'
//     },
//     wrap: {
//       default: '',
//       type: 'string'
//     },
//     alignment: {
//       default: 'none',
//       type: 'string'
//     }
//   },
//
//   edit: (props) => {
//     const {
//       // className,
//       attributes: {
//         post,
//         link,
//         wrap,
//         alignment
//       }
//     } = props;
//
//     return (
//       <div>
//         {
//           <BlockControls>
//             <AlignmentToolbar
//               value={alignment}
//               onChange={(value) => {
//                 props.setAttributes({ alignment: value === undefined ? 'none' : value });
//               }}
//             />
//           </BlockControls>
//         }
//         {
//           <InspectorControls>
//             <TextControl
//               label={_x('Post ID', 'Blocks: Post Title', 'gnetwork')}
//               help={_x('Leave empty for current post.', 'Blocks: Post Title', 'gnetwork')}
//               className='gnetwork-component -code'
//               value={post}
//               onChange={(value) => {
//                 props.setAttributes({ post: value });
//               }}
//             />
//             <TextControl
//               label={_x('Wrap Tag', 'Blocks: Post Title', 'gnetwork')}
//               help={_x('Use any HTML tags for wrapping.', 'Blocks: Post Title', 'gnetwork')}
//               className='gnetwork-component -code'
//               value={wrap}
//               onChange={(value) => {
//                 props.setAttributes({ wrap: value });
//               }}
//             />
//             <ToggleControl
//               label={_x('Link to Post', 'Blocks: Post Title', 'gnetwork')}
//               checked={link}
//               onChange={(value) => {
//                 props.setAttributes({ link: value });
//               }}
//             />
//           </InspectorControls>
//         }
//         <ServerSideRender
//           block='gnetwork/post-title'
//           attributes={props.attributes}
//           // className={className} // https://core.trac.wordpress.org/ticket/45882
//           urlQueryArgs={{ post_id: getCurrentPostId() }} // https://wordpress.stackexchange.com/a/320681/
//         />
//       </div>
//     );
//   },
//
//   save: (props) => {
//     return null;
//   },
//   transforms: {
//     from: [
//       {
//         type: 'shortcode',
//         tag: 'post-title'
//       }
//     ]
//   }
// });
}();
/******/ })()
;
//# sourceMappingURL=index.js.map