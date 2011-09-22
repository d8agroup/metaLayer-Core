from flask import Flask, request, jsonify
from configuration import *
import logging
import logging.handlers


app = Flask(__name__)

logging.basicConfig(level=getattr(logging, LOG_LEVEL_MASK))
logger = logging.getLogger('metaLayer-core')
formatter = logging.Formatter('%(created)f, %(name)s, %(levelname)s, %(module)s, %(funcName)s, %(lineno)s, %(message)s')
logging_handler = logging.handlers.TimedRotatingFileHandler(LOGFILE_NAME, when='d', interval=1, backupCount=30, encoding=None, delay=False, utc=False)
logging_handler.setFormatter(formatter)
logger.addHandler(logging_handler)

import services

@app.route('/services/<int:version>/<service_name>', methods=['POST'])
def run_service(version, service_name):
    
    logger.info("run_service - METHOD STARTED with parameters: version=%i and service_name=%s" % (version, service_name))
    
    if 'api_key' not in request.form:
        logger.error("run_server - ERROR post variable api_key not supplied")
        return jsonify(ERROR_RESPONSE_NOAPI)
    
    service_and_version = "%s_%i" % (service_name.lower(), version)
    
    try:
        service = getattr(services, service_and_version)
    except Exception, e:
        logger.error('run_server - ERROR %s' % e)
        if MASK_ERRORS:
            return jsonify(ERROR_RESPONSE_UNKNOWNSERVICEVERSION)
        raise e
    
    logger.info("run_service - METHOD ENDED")
    
    return jsonify(service(request))