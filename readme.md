BoltBB
======

```
topics:
    name: Topics
    singular_name: Topic
    fields:
        title:
            type: text
            class: large
            group: topic
        body:
            type: html
            height: 300px
        author:
            type: text
            variant: inline
            info: "Something, something, Dark Side"
            readonly: true
            group: Info
        authorip:
            type: text
            variant: inline
            label: "IP address"
            readonly: true
        forum:
            type: integer
            variant: inline
            readonly: true
        state:
            type: select
            values: [ open, closed ]
            variant: inline
        visibility:
            type: select
            values: [ nomal, pinned, global ]
            variant: inline
        subscribers:
            type: textarea
            readonly: true
            hidden: true
    default_status: published

replies:
    name: Replies
    singular_name: Reply
    fields:
        title:
            type: text
            class: large
            group: Reply        
        body:
            type: html
            height: 300px
        author:
            type: text
            variant: inline
            readonly: true
            group: Info
        authorip:
            type: text
            variant: inline
            label: "IP address"
            readonly: true
        forum:
            type: text
            variant: inline
            readonly: true
        topic:
            type: text
            variant: inline
            readonly: true
    default_status: published
```