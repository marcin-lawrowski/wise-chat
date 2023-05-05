import { __ } from '@wordpress/i18n';
import { useBlockProps, ColorPalette, InspectorControls, BlockControls } from '@wordpress/block-editor';
import { TextControl, PanelBody, CheckboxControl, SelectControl, ExternalLink } from '@wordpress/components';
import './editor.scss';
import ServerSideRender from '@wordpress/server-side-render';

import { useRef, useLayoutEffect } from '@wordpress/element';
import React from "react";

let chatInstances = [];
function initAccordion(mutations) {
    for ( let mutation of mutations ) {
        if ('childList' === mutation.type && mutation.addedNodes[0] && jQuery( '.wcContainer', mutation.addedNodes[0] ).length >= 1) {
            const chatId = jQuery('.wcContainer', mutation.addedNodes[0]).attr('id');
            if (chatId && !chatInstances.includes(chatId)) {
                chatInstances = [ ...chatInstances, chatId];
                _wiseChat.init(jQuery('#' + chatId));
                console.log( 'chat loaded', chatId);
            }
        }
    }
}

export default function Edit( { attributes, setAttributes } ) {
    const ref = useRef( null );

    useLayoutEffect( () => {
        let observer;

        if (ref.current) {
            observer = new MutationObserver( initAccordion );
            observer.observe(ref.current, {
                childList: true,
                subtree: true,
            });
        }

        return () => {
            if (observer) {
                observer.disconnect();
            }
        };
    }, [] );

    const renderSettingsLink = (tag) => {
        const url = _wiseChatData.siteUrl + '/wp-admin/options-general.php?page=wise-chat-admin#tab=' + tag;

        return <div>
            <a href={ url } className="" target="_blank">{ __('Advanced Settings') }</a><br />
            <ExternalLink href="https://kainex.pl/projects/wp-plugins/wise-chat-pro?utm_source=wisechat&utm_medium=block&utm_campaign=settings" rel="noopener noreferrer" style={{ backgroundColor: '#4f3b5e', color: '#fff', textDecoration: 'none', padding: 7, display: 'inline-block', marginTop: 7 }} target="_blank">Check Wise Chat Pro</ExternalLink>
        </div>;
    }

    return (
        <div { ...useBlockProps( { ref } ) }>
            <BlockControls>
                <ExternalLink href="https://kainex.pl/projects/wp-plugins/wise-chat-pro?utm_source=wisechat&utm_medium=block&utm_campaign=settings" rel="noopener noreferrer" style={{ backgroundColor: '#4f3b5e', color: '#fff', textDecoration: 'none', padding: 7, display: 'flex', alignItems: 'center' }} target="_blank">Check Wise Chat Pro</ExternalLink>
            </BlockControls>
            <InspectorControls key="setting">
                <PanelBody title={ __( 'Chat Settings' ) } initialOpen={ true }>
                    <TextControl
                        label="Channel"
                        help="Name the channel you want to open"
                        value={ attributes.channel }
                        onChange={ value => setAttributes( { channel: value } ) }
                    />
                    <CheckboxControl
                        label="Disable Anonymous Users"
                        checked={ attributes.access_mode }
                        onChange={ value => setAttributes( { access_mode: value } ) }
                        help="Only logged in WordPress users are allowed to enter the chat"
                    />
                    <CheckboxControl
                        label="Require Username"
                        checked={ attributes.force_user_name_selection }
                        onChange={ value => setAttributes( { force_user_name_selection: value } ) }
                        help="Forces non-logged in users to provide a name before entering the chat"
                    />
                    <TextControl
                        label="Window Title"
                        value={ attributes.window_title }
                        onChange={ value => setAttributes( { window_title: value } ) }
                    />
                    <SelectControl
                        label="Theme"
                        value={ attributes.theme }
                        options={ [
                            { value: '', label: 'Default' },
                            { value: 'lightgray', label: 'Light Gray' },
                            { value: 'colddark', label: 'Cold Dark' },
                            { value: 'airflow', label: 'Air Flow' },
                        ] }
                         onChange={ value => setAttributes( { theme: value } ) }
                    />
                     <TextControl
                        label="Width"
                        value={ attributes.chat_width }
                        onChange={ value => setAttributes( { chat_width: value } ) }
                    />
                     <TextControl
                        label="Height"
                        value={ attributes.chat_height }
                        onChange={ value => setAttributes( { chat_height: value } ) }
                    />
                    { renderSettingsLink('appearance') }
                </PanelBody>
                <PanelBody title={ __( 'Messages' ) } initialOpen={ false }>
                    <SelectControl
                        label="Messages Order"
                        value={ attributes.messages_order }
                        options={ [
                            { value: '', label: 'Newest on the bottom' },
                            { value: 'descending', label: 'Newest on the top' }
                        ] }
                         onChange={ value => setAttributes( { messages_order: value } ) }
                    />
                    <SelectControl
                        label="Message Time Mode"
                        value={ attributes.messages_time_mode }
                        options={ [
                            { value: 'hidden', label: 'Hidden' },
                            { value: '', label: 'Full' },
                            { value: 'elapsed', label: 'Elapsed' }
                        ] }
                         onChange={ value => setAttributes( { messages_time_mode: value } ) }
                    />
                    <CheckboxControl
                        label="Show Avatars"
                        checked={ attributes.show_avatars }
                        onChange={ value => setAttributes( { show_avatars: value } ) }
                    />
                    <label>Background Color</label>
                    <ColorPalette
                        heading="Background Color"
                        value={ attributes.background_color }
                        onChange={ value => setAttributes( { background_color: value } ) }
                    />
                    <label>Font Color</label>
                    <ColorPalette
                        heading="Font Color"
                        value={ attributes.text_color }
                        onChange={ value => setAttributes( { text_color: value } ) }
                    />
                    { renderSettingsLink('appearance') }
                </PanelBody>

                <PanelBody title={ __( 'Input' ) } initialOpen={ false }>
                     <CheckboxControl
                        label="Show Emoticon Button"
                        checked={ attributes.show_emoticon_insert_button }
                        onChange={ value => setAttributes( { show_emoticon_insert_button: value } ) }
                    />
                    <CheckboxControl
                        label="Show Image Button"
                        checked={ attributes.show_image_upload_button }
                        onChange={ value => setAttributes( { show_image_upload_button: value } ) }
                    />
                    <CheckboxControl
                        label="Show File Button"
                        checked={ attributes.show_file_upload_button }
                        onChange={ value => setAttributes( { show_file_upload_button: value } ) }
                    />
                    <CheckboxControl
                        label="Show Submit Button"
                        checked={ attributes.show_message_submit_button }
                        onChange={ value => setAttributes( { show_message_submit_button: value } ) }
                    />
                    <CheckboxControl
                        label="Multiline Messages"
                        checked={ attributes.multiline_support }
                        onChange={ value => setAttributes( { multiline_support: value } ) }
                    />
                    <CheckboxControl
                        label="Show User Name"
                        checked={ attributes.show_user_name }
                        onChange={ value => setAttributes( { show_user_name: value } ) }
                    />
                    <SelectControl
                        label="Input Location"
                        value={ attributes.input_controls_location }
                        options={ [
                            { value: '', label: 'Bottom' },
                            { value: 'top', label: 'Top' }
                        ] }
                         onChange={ value => setAttributes( { input_controls_location: value } ) }
                    />
                    <label>Background Color</label>
                    <ColorPalette
                        heading="Background Color"
                        value={ attributes.background_color_input }
                        onChange={ value => setAttributes( { background_color_input: value } ) }
                    />
                    <label>Font Color</label>
                    <ColorPalette
                        heading="Font Color"
                        value={ attributes.text_color_input_field }
                        onChange={ value => setAttributes( { text_color_input_field: value } ) }
                    />
                    { renderSettingsLink('appearance') }
                </PanelBody>
                <PanelBody title={ __( 'Browser' ) } initialOpen={ false }>
                     <CheckboxControl
                        label="Enabled"
                        checked={ attributes.show_users }
                        onChange={ value => setAttributes( { show_users: value } ) }
                    />
                    <SelectControl
                        label="Location"
                        value={ attributes.browser_location }
                        options={ [
                            { value: '', label: 'Right' },
                            { value: 'left', label: 'Left' }
                        ] }
                         onChange={ value => setAttributes( { browser_location: value } ) }
                    />
                    <CheckboxControl
                        label="Show Users Search Box"
                        checked={ attributes.show_users_list_search_box }
                        onChange={ value => setAttributes( { show_users_list_search_box: value } ) }
                    />
                    <CheckboxControl
                        label="Show Avatars"
                        checked={ attributes.show_users_list_avatars }
                        onChange={ value => setAttributes( { show_users_list_avatars: value } ) }
                    />
                    <CheckboxControl
                        label="Show National Flags"
                        checked={ attributes.show_users_flags }
                        onChange={ value => setAttributes( { show_users_flags: value } ) }
                    />
                    <CheckboxControl
                        label="Show City And Country"
                        checked={ attributes.show_users_city_and_country }
                        onChange={ value => setAttributes( { show_users_city_and_country: value } ) }
                    />
                    <CheckboxControl
                        label="Show Online / Offline Mark"
                        checked={ attributes.show_users_online_offline_mark }
                        onChange={ value => setAttributes( { show_users_online_offline_mark: value } ) }
                    />
                    <CheckboxControl
                        label="Show Online Users Counter"
                        checked={ attributes.show_users_counter }
                        onChange={ value => setAttributes( { show_users_counter: value } ) }
                    />
                    <label>Background Color</label>
                    <ColorPalette
                        heading="Background Color"
                        value={ attributes.background_color_users_list }
                        onChange={ value => setAttributes( { background_color_users_list: value } ) }
                    />
                    <label>Font Color</label>
                    <ColorPalette
                        heading="Font Color"
                        value={ attributes.text_color_users_list }
                        onChange={ value => setAttributes( { text_color_users_list: value } ) }
                    />
                    { renderSettingsLink('appearance') }
                </PanelBody>
            </InspectorControls>

            <ServerSideRender
                block="kainex/wise-chat"
                attributes={ attributes }
            />
        </div>
    );
}