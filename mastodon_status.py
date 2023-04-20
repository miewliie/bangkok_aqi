""" A module for all mastodon activity """

import os
from mastodon import Mastodon
from read_aqi_outputs import aqi_details

USER = os.getenv('MASTODON_EMAIL')
PASSWORD = os.getenv('MASTODON_PASSWORD')
MASTODON_SERVER = os.getenv('MASTODON_SERVER')


def connect_to_mastodon():
    """ Create a connection to your server. And provide account credential. """

    Mastodon.create_app(
        'pytooterapp',
        api_base_url=MASTODON_SERVER,
        to_file='pytooter_clientcred.secret'
    )

    mastodon = Mastodon(client_id='pytooter_clientcred.secret',)
    mastodon.log_in(
        USER,
        PASSWORD)
    return mastodon


def send_new_status_for(aqi):
    """ Post AQI status and AQI recent map. """

    mastodon = connect_to_mastodon()

    image_id = mastodon.media_post(aqi["map"])
    post_dict = mastodon.status_post(
        aqi["th_en_status"], in_reply_to_id=None, media_ids=image_id)
    print("post id: ", post_dict.id)


def send_new_avatar_for(aqi):
    """ Update AQI avatar, display name and bio. """

    mastodon = connect_to_mastodon()

    image_url = aqi["avatar"]
    image_extension = image_url.split(".")[-1]

    avatar_updated = mastodon.account_update_credentials(
        display_name=aqi["name"], note=aqi["name"],
        avatar=aqi["avatar"], avatar_mime_type=f"image/{image_extension}")
    print("avatar id: ", avatar_updated.id)


if __name__ == '__main__':
    aqi_info = aqi_details()
    if aqi_info:
        print(aqi_info)
        send_new_status_for(aqi_info)
        send_new_avatar_for(aqi_info)
