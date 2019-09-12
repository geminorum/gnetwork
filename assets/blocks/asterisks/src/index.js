const { registerBlockType } = wp.blocks;
const { _x } = wp.i18n;

registerBlockType('gnetwork/asterisks', {
  title: _x('Asterisks', 'Blocks: Asterisks', 'gnetwork'),
  description: _x('Displays three asterisks as a separator.', 'Blocks: Asterisks', 'gnetwork'),
  icon: 'star-filled',
  category: 'layout',
  keywords: [
    _x('asterisks', 'Blocks', 'gnetwork'),
    _x('separator', 'Blocks', 'gnetwork'),
    _x('stars', 'Blocks', 'gnetwork')
  ],
  supports: {
    reusable: false
  },
  edit: (props) => {
    return <div className={props.className}>&#x274b;&nbsp;&#x274b;&nbsp;&#x274b;</div>;
  },
  save: (props) => {
    return <div className={props.className}>&#x274b;&nbsp;&#x274b;&nbsp;&#x274b;</div>;
  },
  transforms: {
    from: [
      {
        type: 'shortcode',
        tag: 'three-asterisks'
      }
    ]
  }
});
