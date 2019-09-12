const { registerBlockType } = wp.blocks;
const { InspectorControls, BlockControls, AlignmentToolbar } = wp.editor;
const { ServerSideRender, TextControl, ToggleControl } = wp.components;
const { getCurrentPostId } = wp.data.select('core/editor');
const { _x } = wp.i18n;

registerBlockType('gnetwork/post-title', {
  title: _x('Post Title', 'Blocks: Post Title', 'gnetwork'),
  description: _x('Displays selected post title in an HTML tag.', 'Blocks: Post Title', 'gnetwork'),
  icon: 'editor-textcolor',
  category: 'common',
  keywords: [
    _x('title', 'Blocks', 'gnetwork'),
    _x('post title', 'Blocks', 'gnetwork'),
    _x('heading', 'Blocks', 'gnetwork')
  ],
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

  edit: (props) => {
    const {
      // className,
      attributes: {
        post,
        link,
        wrap,
        alignment
      }
    } = props;

    return (
      <div>
        {
          <BlockControls>
            <AlignmentToolbar
              value={alignment}
              onChange={(value) => {
                props.setAttributes({ alignment: value === undefined ? 'none' : value });
              }}
            />
          </BlockControls>
        }
        {
          <InspectorControls>
            <TextControl
              label={_x('Post ID', 'Blocks: Post Title', 'gnetwork')}
              help={_x('Leave empty for current post.', 'Blocks: Post Title', 'gnetwork')}
              className='gnetwork-component -code'
              value={post}
              onChange={(value) => {
                props.setAttributes({ post: value });
              }}
            />
            <TextControl
              label={_x('Wrap Tag', 'Blocks: Post Title', 'gnetwork')}
              help={_x('Use any HTML tags for wrapping.', 'Blocks: Post Title', 'gnetwork')}
              className='gnetwork-component -code'
              value={wrap}
              onChange={(value) => {
                props.setAttributes({ wrap: value });
              }}
            />
            <ToggleControl
              label={_x('Link to Post', 'Blocks: Post Title', 'gnetwork')}
              checked={link}
              onChange={(value) => {
                props.setAttributes({ link: value });
              }}
            />
          </InspectorControls>
        }
        <ServerSideRender
          block='gnetwork/post-title'
          attributes={props.attributes}
          // className={className} // https://core.trac.wordpress.org/ticket/45882
          urlQueryArgs={{ post_id: getCurrentPostId() }} // https://wordpress.stackexchange.com/a/320681/
        />
      </div>
    );
  },

  save: (props) => {
    return null;
  },
  transforms: {
    from: [
      {
        type: 'shortcode',
        tag: 'post-title'
      }
    ]
  }
});
