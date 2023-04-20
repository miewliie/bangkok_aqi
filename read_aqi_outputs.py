""" A simple module for read outputs from file and prepare outputs for others. """

import json


def aqi_details():
    """ Read all AQI outputs from Json file. And return outputs """

    with open('./outputs/aqi-outputs.json', "r", encoding="utf-8") as output_file:
        data = json.loads(output_file.read())
        return data
