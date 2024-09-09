#include "gc3tk.h"
#include "headers/statics/strutils.h"
namespace protspace
{


std::string getTG_DIR_HOME()
{
    return getEnvVariable("TG_DIR");


}
std::string getTG_DIR_STATIC()
{
    return getTG_DIR_HOME()+"/BACKEND/STATIC_DATA/XRAY/";
}
std::string getHETListFlat()
{
    return getTG_DIR_HOME()+"/PRD_DATA/XRAY_TPL/HETLIST";
}


std::string getHETList()
{
    return getTG_DIR_HOME()+"/PRD_DATA/XRAY_TPL/HETLIST.objx";
}

std::string getHETListPosit()
{
    return getTG_DIR_HOME()+"/PRD_DATA/XRAY_TPL/HETList.posit";
}

static std::string altTG_DIR_PATH="";
void setAltTG_DIR_PATH(const std::string& pPath){altTG_DIR_PATH=pPath;}
std::string getAltTG_DIR_PATH(){return altTG_DIR_PATH;}




}
