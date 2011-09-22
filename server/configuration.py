
#ERROR HANDELING
MASK_ERRORS = True

#Logging
LOG_LEVEL_MASK = 'ERROR'
LOGFILE_NAME = '/var/log/metaLayer-core.log'

#RESPONSE MESSAGES
ERROR_RESPONSE_NOAPI = { 'status':'failed', 'code':101, 'error':'The required POST field \'api_key\' was not supplied' }
ERROR_RESPONSE_UNKNOWNSERVICEVERSION = { 'status':'failed', 'code':102, 'error':'The service and version combination could not be loaded' }

ERROR_DATALAYER_NOTEXT = {'status':'failed', 'code':111, 'error':'The required POST field \'text\' was not supplied' }
ERROR_DATALAYER_NLPSERVICE = { 'status':'failed', 'code':112, 'error':'There was a communication issue with the nlp service' }
ERROR_DATALAYER_LOCATIONSERVICE = { 'status':'failed', 'code':113, 'error':'There was a communication issue with the location service' }

SERVICE_ENDPOINTS = {
    'nlp':'http://50.57.38.102:5002/api/tag',
    'ocr':'http://50.57.38.134/services/1/ocr',
    'yahooplcemaker':'http://wherein.yahooapis.com/v1/document',
    'imaging':'http://50.57.101.111/processimage'
}

#API KEYS
APIKEYS = {
    'yahooplacemaker':'H6qFYGLV34Ebv5h6qUSowFuRj9NQnWBO2BzjdFOrjMCor3oiIE92Zj79_46lByT0h1P9daysoOuGLZT8'
}