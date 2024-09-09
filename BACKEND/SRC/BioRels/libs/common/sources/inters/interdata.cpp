#include <math.h>
#include <sstream>
#include <fstream>
#ifdef WINDOWS
#define _USE_MATH_DEFINES
#include <cmath>
#endif
#include <math.h>
#include "headers/inters/interdata.h"
#include "headers/molecule/macromole.h"
#include "headers/statics/intertypes.h"
#include "headers/molecule/mmring_utils.h"

#define RAD_2_DEG 180/M_PI

std::string protspace::InterData::getAtomData(const MMAtom& pAtom,
                                              const bool& isSDLIG,
                                              const bool& start)
{
    size_t ring=getRingPosFromCenter(pAtom);
    const bool isNotRing(ring==pAtom.getMolecule().numRings());
    const MMResidue& res=(isNotRing)?
                pAtom.getResidue():
                pAtom.getMolecule().getRing(ring).getResidue();
    std::ostringstream oss;
    //oss<<pAtom.getName()<<"\t";
    if (isSDLIG && !start)     oss<< "/\t/\t/\tLIGAND\t";
    else
    {
        oss   <<res.getName()<<"\t"
              <<res.getFID()<<"\t"
              <<res.getChainName()<<"\t"
              <<RESTYPE::typeToName.at(res.getResType())<<"\t";
        if (isNotRing)oss<<pAtom.getName()<<"\t";
        else
        {
            const protspace::MMRing& ringT=pAtom.getMolecule().getRing(ring);
            std::vector<std::string> l;
            std::string t("");
            for(size_t i=0;i<ringT.numAtoms();++i)
                l.push_back(ringT.getAtom(i).getName());
            std::sort(l.begin(),l.end());
            for(const std::string& p:l)
                t+=p+"/";
            t.pop_back();
            oss<<t<<"\t";
        }
    }
    return oss.str();
}

void protspace::InterData::toText(const std::string& head, const bool& isSDLIG, const bool& bothSide)
try{

    std::ostringstream oss;


    for(const InterObj& inter:mListInteractions)
    {
        if (!inter.mIsUsed)continue;
        oss.str("");
        for(size_t i=0;i<=1;++i)
        {

            const MMAtom& atom1=(i==0)?*inter.mAtom1:*inter.mAtom2;
            const MMAtom& atom2=(i==0)?*inter.mAtom2:*inter.mAtom1;
            if (!head.empty())oss<<head<<"\t";
            oss<<getAtomData(atom1,isSDLIG,i)
               <<getAtomData(atom2,isSDLIG,i)
               <<inter.mDistance<<"\t"
               <<(((inter.mAngle)==100000)?"":std::to_string((inter.mAngle*RAD_2_DEG)))
               <<"\t"<<inter.interToString()<<"\n";
        }

        mListTextInters.push_back(oss.str());
    }
}
catch(ProtExcept &e)
{
assert(e.getId()!="310701");///Atom must have a residue
assert(e.getId()!="352501");///Ring must exists
e.addHierarchy("InterData::toText");
throw;

}
catch(std::out_of_range &e)
{
    assert(1==0);
}




size_t protspace::InterData::toMolecule(const bool &onlyUsed)
{
    try{

        MacroMole* mole=new MacroMole(mName,MOLETYPE::INTERS);
        MacroMole&molecule=mMolecules.get(mMolecules.add(mole));
        std::map<unsigned char,MMResidue*> listRP,listRC,listRL;
        size_t i=0;
        for (auto it = INTER::ItoN.begin();it != INTER::ItoN.end();++it)
        {

           std:: ostringstream oss;
            oss<< (*it).second.resName<<"P";
            listRP.insert(std::make_pair((*it).first, &molecule.addResidue(oss.str(),"A",i,false)));++i;
            oss.str("");
            oss<< (*it).second.resName<<"L";
            listRL.insert(std::make_pair((*it).first, &molecule.addResidue(oss.str(),"A",i,false)));++i;
            oss.str("");
            oss<< (*it).second.resName<<"C";
            listRC.insert(std::make_pair((*it).first, &molecule.addResidue(oss.str(),"A",i,false)));++i;

        }


        for(size_t iInter=0;iInter< mListInteractions.size();++iInter)
        {

            const InterObj& inter=mListInteractions.at(iInter);

            try{
                if (onlyUsed && !inter.mIsUsed)continue;
                MMAtom* atmIP=(MMAtom*)NULL,
                        * atmIC=(MMAtom*)NULL,
                        * atmIL=(MMAtom*)NULL;
                const Coords center((inter.mAtom1->pos()+inter.mAtom2->pos())/2);
                for (size_t iAtm=0;iAtm< molecule.numAtoms();++iAtm)
                {
                    MMAtom& atom=molecule.getAtom(iAtm);
                    if (atom.getName()!= INTER::ItoN.at(inter.mType).atmName)continue;
                    if (atom.pos()== inter.mAtom1->pos())atmIP=&atom;
                    if (atom.pos()== center)       atmIC=&atom;
                    if (atom.pos()== inter.mAtom2->pos())atmIL=&atom;
                }
                if (atmIP== (MMAtom*)NULL)
                {
                    MMAtom& atomP=molecule.addAtom(*listRP.at(inter.mType),
                                                   inter.mAtom1->pos(),
                                                   INTER::ItoN.at(inter.mType).atmName,
                                                   INTER::ItoN.at(inter.mType).atmMOL2,
                                                   INTER::ItoN.at(inter.mType).atmElem);
                    atmIP=&atomP;

                }

                if (atmIC== (MMAtom*)NULL)
                {
                    MMAtom& atomC=molecule.addAtom(*listRC.at(inter.mType),
                                                   center,
                                                   INTER::ItoN.at(inter.mType).atmName,
                                                   INTER::ItoN.at(inter.mType).atmMOL2,
                                                   INTER::ItoN.at(inter.mType).atmElem);
                    atmIC=&atomC;

                }
                if (atmIL== (MMAtom*)NULL)
                {
                    MMAtom& atomL=molecule.addAtom( *listRL.at(inter.mType),
                                                    inter.mAtom2->pos(),
                                                    INTER::ItoN.at(inter.mType).atmName,
                                                    INTER::ItoN.at(inter.mType).atmMOL2,
                                                    INTER::ItoN.at(inter.mType).atmElem
                                                    );
                    atmIL=&atomL;

                }
                //        if (fabs(atmIP->pos().distance(Coords(0,0,0)))<0.001 ||
                //            fabs(atmIL->pos().distance(Coords(0,0,0)))<0.001 ||
                //            fabs(atmIC->pos().distance(Coords(0,0,0)))<0.001)
                //cout << inter.toString();
                if (!atmIP->hasBondWith(*atmIC) && atmIP!=atmIC)
                    molecule.addBond(*atmIP,*atmIC,BOND::SINGLE,molecule.numBonds());


                if (!atmIC->hasBondWith(*atmIL) && atmIC!=atmIL)
                    molecule.addBond(*atmIC,*atmIL,BOND::SINGLE,molecule.numBonds());
            }catch(ProtExcept &e)
            {
                e.addDescription("Interaction involved:\n"+inter.toString());
                throw;
            }
        }
    }
    catch(ProtExcept &e)
    {

        assert(e.getId()!="351501"&& e.getId()!="350101" && e.getId()!="350601");///Not an alias molecule
        assert(e.getId()!="310802");///Atom type and MOL2 MUST exists since we define them
        assert(e.getId()!="310101");///Atom type and MOL2 MUST exists since we define them
        assert(e.getId()!="310102");///Atom type and MOL2 MUST exists since we define them
        assert(e.getId()!="350302");///Atom type and MOL2 MUST exists since we define them
        assert(e.getId()!="350602"&&
               e.getId()!="350603"&&
               e.getId()!="350604");///Atom must be in the molecule
        assert(e.getId()!="351503" &&
               e.getId()!="351504" &&
               e.getId()!="350301");///Residue definition must be good since we define them
        assert(e.getId()!="030401");///Atom must exist


        e.addHierarchy("nbInter::toMolecule");
        throw;
    }
    return mMolecules.size()-1;
}




void protspace::InterData::saveText(const std::string& pFile,const bool& atEnd,const bool& wHeader)
{


    std::ofstream ofs(pFile.c_str(),(atEnd)?std::ofstream::app:
                                       std::ofstream::out);
    ///No file - => wHeader =true => HEADER
    ///File Exists => wHeader=false
    ///  -> atEnd => true=> NO HEADER
    ///  -> atEnd = false=> HEADER
    if (wHeader || (!wHeader && !atEnd))
        ofs <<"ENTRY_NAME\tRES_NAME1\tRES_ID1\tRES_CHAIN1\tRES_TYPE1\tATOM_NAME1\t"
           <<"RES_NAME2\tRES_ID2\tRES_CHAIN2\tRES_TYPE2\tATOM_NAME2\t"
          <<"DISTANCE\tANGLE\tTYPE\n";
    for(const std::string& text:mListTextInters)    ofs<<text;

    ofs.close();
}



void protspace::InterData::clear()
{
    mListInteractions.clear();
    mMolecules.clear();
    mListTextInters.clear();
    mName="";

}
void protspace::InterData::erase(size_t pos){
    ///TODO BAD CODE. VERY VERY BAD CODE
    std::vector<InterObj> objd;
    for(size_t i=0;i<mListInteractions.size();++i)
        if (i!=pos)objd.push_back(mListInteractions.at(i));
    mListInteractions.clear();
    for(InterObj obj:objd)  mListInteractions.push_back(obj);
}

void protspace::InterData::unique()
{
    std::sort(mListInteractions.begin(),
              mListInteractions.end(),
              less_than_inter());
    bool change=false;
    do
    {
        change=false;
    for (size_t i=0;i<mListInteractions.size();++i)
    {
        for (size_t j=i+1;j<mListInteractions.size();++j)
        {
            if (!(getInter(i)==getInter(j)))continue;
            mListInteractions.erase(mListInteractions.begin()+j);
            change=true;
            --j;
        }

    }
    }while(change);
}
