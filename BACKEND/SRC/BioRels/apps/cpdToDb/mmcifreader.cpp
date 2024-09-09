#include <iostream>
#include <fstream>
#include <algorithm>
#include "mmcifreader.h"
#include "headers/statics/logger.h"
#include "headers/statics/protpool.h"
#include "headers/parser/ofstream_utils.h"
MMCifReader::  MMCifReader(const std::string& path):
    mCountCheck({{"chem",24},{"atom",18},{"bond",7},
                {"comp_descriptor",5},{"comp_identifier",5},
               {"comp_audit",4},{"comp_feature",4}}),
    mGroupFunc({{"",&MMCifReader::chemCompToRes},
               {"atom",&MMCifReader::atomToRes},
               {"bond",&MMCifReader::bondToRes},
               {"comp_descriptor",&MMCifReader::compDesToRes},
               {"comp_identifier",&MMCifReader::compIdenToRes}}),
    mIsReady(false)
{
   if (!path.empty()) getFilePos(path);

}

void MMCifReader::setCifFile(const std::string& path)
try{
    getFilePos(path);
}catch(ProtExcept &e)
{
    e.addHierarchy("MMCifReader::setCifFile");
    e.addDescription("File given : "+path);
    throw;
}

void MMCifReader::getFilePos(const std::string & path)
{
    mIfs.open(path);
    if (!mIfs.is_open())
        throw_line("2330101",
                         "MMCifReader::getFilePos",
                         "Unable to open file "+path);
    protspace::StringPoolObj line;
    while(!mIfs.eof())
    {
        const size_t pos=mIfs.tellg();
        safeGetline(mIfs,line.get());
        if (line.get().length()<5)continue;
        if (line.get().substr(0,5)!="data_")continue;
        mFilePos.insert(std::make_pair(line.get().substr(5),pos));
    }

    mIsReady=true;
    mIfs.clear();
    mIfs.seekg(0,std::ios::beg);
}





bool MMCifReader::checkSize(const std::vector<std::vector<std::string>>& list,
                            const size_t& nSize) const
{
    std::vector<size_t> data;
    for(size_t i=0;i < list.size();++i)
        data.push_back( list.at(i).size());

    std::sort(data.begin(),data.end());
    data.resize(std::distance(data.begin(),std::unique(data.begin(),data.end())));
    if (data.at(0)!=nSize)return false;
    if (data.size()!=1)return false;
    return true;
}


bool MMCifReader::loadNext(MMCifRes& entry,const std::string& selHET)
try
{

    protspace::StringPoolObj lineO;
    std::string& line=lineO.get();
    if (selHET !="")
    {
        if (mFilePos.find(selHET)!= mFilePos.end())
            mIfs.seekg(mFilePos.at(selHET),std::ios::beg);

        else throw_line("2330201","MMCifReader::loadNext","Unable to find "+selHET);
    }
    size_t nEmpty=0;
    while(!mIfs.eof())
    {
        safeGetline(mIfs,line);

        if (line.empty())nEmpty++;    else nEmpty=0;
        if (nEmpty ==10)break;
        if(line.substr(0,5) != "data_")continue;
        HETCode = line.substr(5);

        entry.setHET(HETCode);
        if (selHET!="" && HETCode != selHET)continue;
        //cout <<HETCode<<"\t"<<endl;
        safeGetline(mIfs,line);// # line
        std::string groupHead;
        std::vector<std::vector<std::string>> blockres;
        size_t nBlock=0;
        do {

            blockres.clear();
            if(!readBlock(groupHead,blockres))break;
           // std::cout << HETCode<<"  |"<<groupHead<<"| " << blockres.size()<<std::endl;
//            for(auto it:blockres)
//            {

//for (auto it2:it)std::cout << it2<<std::endl;
////            }

            if (mCountCheck.find(groupHead)!= mCountCheck.end()){
                // Checking that the group have the correct number of lines
                // Depending on the groupHead name

                if(!checkSize(blockres,mCountCheck.at(groupHead)))
                    std::cerr << HETCode<<"\t"<<groupHead<<std::endl;
            }//else std::cerr << HETCode<<"\t"<<groupHead<<"\tNOT FOUND"<<std::endl;

            // Analyzing group
            if (mGroupFunc.find(groupHead)!= mGroupFunc.end())
            {
                // Each group are defined in mGroupFunc as a pointer to function
                PtrFunct ptf=mGroupFunc.at(groupHead);
                (this->*ptf)(blockres,entry);
            }

            nBlock++;

            std::string line,line2;
            const int pos=mIfs.tellg();
            getline(mIfs,line);
            getline(mIfs,line2);
            mIfs.seekg(pos,std::ios::beg);
            if ( line.substr(0,5)== "data_")break;
            if (line2.substr(0,5)== "data_")break;

        }while (!mIfs.eof());
        //  cout <<HETCode<<"\t"<<nBlock<<endl;


        return true;
    }


    return false;
}
catch(ProtExcept &e)
{
    e.addHierarchy("mmcifreader::loadNext");
    throw;
}

std::string MMCifReader::getFullData(const bool& useHET)
{
    //  cout <<"START FULL DATA"<<endl;
    int pos=mIfs.tellg();
    std::string value="",line="";
    getline(mIfs,line);
    if (mIfs.eof())return "";

    size_t n=0;
    while(
          ((!useHET&& line.substr(0,5) != "_chem")
           ||(useHET && line.substr(0,3) != HETCode))
          && line.substr(0,1)!="#"
          )
    {
        pos = mIfs.tellg();
        value+=line;
        //        cout <<"ADDD  "<<line.substr(0,5)<<endl;
        getline(mIfs,line);
        //        cout <<"FULL : " <<mIfs.eof()<< line<<endl;
        ++n;
        if (n==10)break;
        if (mIfs.eof())break;
    }
    //cout <<"END FULL DATA"<<endl;
    mIfs.seekg(pos,std::ios::beg);
    return value;

}



void MMCifReader::loadCategory(std::vector<std::string>& categoriesList,
                               std::vector<std::string>& block,
                               const std::string& line)
{
    const size_t pos_start=line.find_first_of(".");
    const size_t pos_end  =line.find_first_of(" ");
    const protspace::StringPoolObj category(line.substr(pos_start+1,pos_end-pos_start-1));
    categoriesList.push_back(category.get());

    if (mIsLoop) return;

    // Case value in the same line
    const size_t pos_val_start=line.find_first_not_of(" ",pos_end);
    //cout <<"CAT"<<endl;
    if (pos_val_start != std::string::npos)   block.push_back(removeQuote(line.substr(pos_val_start)));
    else                               block.push_back(removeQuote(getFullData()));

}


std::string MMCifReader::removeQuote(const std::string& line)const
{

    if (line.empty()) return "";
    protspace::StringPoolObj newlineO("");
    std::string& newline=newlineO.get();
    bool step=false;
    for(size_t i=0;i<line.length();++i)
    {
        const protspace::StringPoolObj chrO(line.substr(i,1));
        const std::string& chr=chrO.get();
        if (step) newline+=chr;
        else if (chr==" "||chr==";"||chr=="\"") continue;
        else { newline+=chr;step=true;}
    }
    size_t stops=0;step=false;
    newline.erase(
                std::remove( newline.begin(), newline.end(), '\"' ),
                newline.end()
                );
    for(size_t i=newline.length()-1;;--i)
    {
        const protspace::StringPoolObj chrO(newline.substr(i,1));
        const std::string& chr=chrO.get();

        if (chr==" "||chr==";"||chr=="\"") {continue;}
        else if(chr=="\""){step=true;continue;}
        stops=i;break;
    }

    if (!step)stops++;

    return newline.substr(0,stops);
}


bool MMCifReader::readBlock(std::string& groupHead,
                            std::vector<std::vector<std::string>>& blockres)
try{
    if (mIfs.eof())return false;
    protspace::StringPoolObj lineO(""),  valueO("");
    std::string& line=lineO.get(), &value=valueO.get();
    const int pos=mIfs.tellg();
    safeGetline(mIfs,line);
    if (mIfs.eof())return false;

    if (line.substr(0,1)!= "#" && line.substr(0,5)!="loop_")
        mIfs.seekg(pos,std::ios::beg);


    std::vector<std::string> categoriesList;
    mIsCat=true;
    std::vector<std::string> block;
    mIsLoop=false;
    if (line.substr(0,5)=="loop_") mIsLoop=true;
    bool fGroupName=false;

    size_t nEmpty=0;
    groupHead="";
    do
    {

        safeGetline(mIfs,line);
      //  std::cout <<"LINE:"<<line<<std::endl;
        if (line.empty())nEmpty++;
        else nEmpty=0;
        if (nEmpty ==10)break;
        if (mIfs.eof()){return false;}
        if (!fGroupName)
        {
            fGroupName=true;
            const size_t pos_end  =line.find_first_of(".");
            if (pos_end != std::string::npos)
            {
                if (pos_end > 10) groupHead=line.substr(11,pos_end-11);
                else groupHead="chem";
            }
        }

        if (line.substr(0,HETCode.length())==HETCode){
            if (mIsCat)blockres.push_back(categoriesList);
            mIsCat=false;}

        if (line.substr(0,1)=="#")break;

        if (mIsCat)
        {

            if (!line.empty())  loadCategory(categoriesList,block,line);

        }
        else
        {

            // if (line.find("\"")==string::npos)continue;
            size_t posStart,posEnd;
            posStart=line.find_first_not_of(" ",HETCode.length());
            block.clear();
            block.push_back(line.substr(0,posStart-1));

            while(posStart != std::string::npos || posStart == line.length())
            {

                const std::string pval=line.substr(posStart,1);
                if (pval=="\"")
                {
                    posEnd=line.find_first_of("\"",posStart+1);
                    value=line.substr(posStart+1,posEnd-posStart-1);
                    posEnd++;
                }
                else
                {
                    posEnd=line.find_first_of(" ",posStart);
                    value=line.substr(posStart,posEnd-posStart);
                }

                posStart =line.find_first_not_of(" ",posEnd);


                block.push_back(removeQuote(value));

            }

            if(mIsLoop && blockres.at(0).size()-block.size()==1)
            {

                block.push_back(removeQuote(getFullData(true)));
            }
            //TESTJD:
            blockres.push_back(block);

        }





    }while (line.substr(0,1)!="#"&& !mIfs.eof());
    //    cout <<groupHead<<" "<<block.size()<<" " << mIsLoop<<endl;
    if (nEmpty==10)return false;
    if (!mIsLoop)
    {
        blockres.push_back(categoriesList);
        blockres.push_back(block);}
    return true;
}
catch(ProtExcept &e)
{
    e.addHierarchy("MMCifReader::readBlock");
    throw;
}

bool MMCifReader::findValue(const std::string& header,
                            const std::vector<std::string>& categories,
                            const std::vector<std::string>& values,
                            std::string& value)const
{
    const std::vector<std::string>::const_iterator it=
            std::find(categories.begin(),
                      categories.end(),header);
    if (it == categories.end())return false;

    const size_t posId=std::distance(categories.begin(),it);
    value= values.at(posId);
    return true;
}



void MMCifReader::chemCompToRes(const std::vector<std::vector<std::string>>&data,
                                MMCifRes& entry)
{


    static const std::vector<std::string> listDbl={"pdbx_formal_charge",
                                              "formula_weight"};
    const std::vector<std::string>& categories=data.at(0);
    const std::vector<std::string>& values=data.at(1);
    std::string value="";
    if(findValue("id",categories,values,value)) entry.setHET(value);

    for(size_t i=0;i<categories.size();++i)
    {
        const std::string& cat = categories.at(i);

        if (std::find(listDbl.begin(),
                 listDbl.end(),
                 cat) != listDbl.end())
        {

            entry.addChemCompDbl(cat,values.at(i));
        }
        else entry.addChemComp(cat,values.at(i));
    }




}

const std::string& sanitize(const std::string &ini)
{
    if (ini=="")return ini;
    protspace::StringPoolObj res("");
    for (size_t i=0;i<ini.length();++i)
    {
        if (ini[i]==' '||ini[i]=='"')continue;
        res.get()+=ini[i];
//        const std::string p=ini.substr(i,1);
//        if (p==" " || p== "\"")continue;
//        res+=p;
    }
    return res.get();
}

void MMCifReader::atomToRes(const std::vector<std::vector<std::string>>&data, MMCifRes &entry)
try{
    //cout <<"ATOM:"<<data.size()<<endl;
    const std::vector<std::string>& categories=data.at(0);
    protspace::StringPoolObj nameO(""),elementO(""),valueO("");
    std::string& name =nameO.get(),element=elementO.get(),value=valueO.get();
    double fcharge=0;
    bool isAromatic=false;
    for(size_t j=1;j<data.size();++j)
    {
        const std::vector<std::string>& values=data.at(j);
        //  cout <<"######"<<endl;

        name="";element="";
        fcharge=0;isAromatic=false;
        value="";
        double x_v=0,y_v=0,z_v=0;
        if (findValue("atom_id",categories,values,value)) name = sanitize(value);
        if (findValue("type_symbol",categories,values,value)) element =sanitize(value);
        if (element == "X" )element = "Du";
        if (element == "D" )element = "H";

        if (findValue("charge",categories,values,value)) fcharge = atof(sanitize(value).c_str());
        if (findValue("pdbx_aromatic_flag",categories,values,value)) isAromatic=(value=="Y")?true:false;
        if (findValue("model_Cartn_x",categories,values,value)) x_v=atof(value.c_str());
        if (findValue("model_Cartn_y",categories,values,value)) y_v=atof(value.c_str());
        if (findValue("model_Cartn_z",categories,values,value)) z_v=atof(value.c_str());

        entry.addAtom(x_v,y_v,z_v,isAromatic,fcharge,element,name);
        //     for(size_t i=0;i<categories.size();++i)
        //     {
        //         const std::string& cat = categories.at(i);
        //        cout <<"CTX:"<< cat<<"\t"<<values.at(i)<<endl;
        //     }
    }
}catch(ProtExcept &e)
{
    e.addHierarchy("MMCifReader::atomToRes");
    throw;

}


void MMCifReader::bondToRes(const std::vector<std::vector<std::string>>& data, MMCifRes &entry)
try{
    const std::vector<std::string>& categories=data.at(0);

protspace::StringPoolObj atom1O(""),atom2O(""),orderO(""),valueO("");
    std::string& atom1(atom1O.get()),atom2(atom2O.get()),order(orderO.get()),value(valueO.get());
    bool arom,stereo;
    int ordinal;
    for(size_t j=1;j<data.size();++j)
    {
        const std::vector<std::string>& values=data.at(j);
        atom1="";atom2="";order="";arom=false;stereo=false;
        ordinal=0;
        if (findValue("atom_id_1",categories,values,value)) atom1 = sanitize(value);
        if (findValue("atom_id_2",categories,values,value)) atom2=sanitize(value);
        if (findValue("value_order",categories,values,value)) order=sanitize(value);
        if (findValue("pdbx_aromatic_flag",categories,values,value)) arom=(value=="T")?true:false;
        if (findValue("pdbx_stereo_config",categories,values,value)) stereo=(value=="T")?true:false;
        if (findValue("pdbx_ordinal",categories,values,value)) ordinal=atoi(value.c_str());
        entry.addBond(atom1,atom2,order,arom,stereo,ordinal);

    }
}catch(ProtExcept &e)
{
    e.addHierarchy("MMCifReader::BondToRes");
    throw;
}
void MMCifReader::compDesToRes(const std::vector<std::vector<std::string>>&data,   MMCifRes &entry)
{
    const std::vector<std::string>& categories=data.at(0);
    protspace::StringPoolObj valueO(""),typeO(""),programO(""),program_versionO(""),descriptorO("");
    std::string& value(valueO.get()),type(typeO.get()),program(programO.get()),program_version(program_versionO.get()),descriptor(descriptorO.get());
    for(size_t j=1;j<data.size();++j)
    {
        const std::vector<std::string>& values=data.at(j);

        type="";
        program="";
        program_version="";
        descriptor=""	;
        if (findValue("type",categories,values,value)) type = sanitize(value);
        if (findValue("program",categories,values,value)) program=sanitize(value);
        if (findValue("program_version",categories,values,value)) program_version=sanitize(value);
        if (findValue("descriptor",categories,values,value)) descriptor=sanitize(value);
        entry.addSMINCHI(type,program,program_version,descriptor);

    }
}



void MMCifReader::compIdenToRes(const std::vector<std::vector<std::string>>&data, MMCifRes &entry)
{
    // systematic name
}


void MMCifReader::getListHET(std::vector<std::string> &list)const
{
    for( auto it=mFilePos.begin();it!=mFilePos.end();++it)
    {
        list.push_back((*it).first);
    }
    LOG("Number to process: "+std::to_string(list.size()));
}
