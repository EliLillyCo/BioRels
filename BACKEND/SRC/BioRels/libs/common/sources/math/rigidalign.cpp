#include "headers/math/rigidalign.h"
#include "headers/statics/protpool.h"
#undef NDEBUG /// Active assertion in release
protspace::ObjectPool<double>& protspace::RigidAlign::sPoolDbl=protspace::ProtPool::Instance().dbl;
protspace::ObjectPool<protspace::Coords>& protspace::RigidAlign::sPoolCoo=protspace::ProtPool::Instance().coord;


protspace::RigidAlign::RigidAlign(const RigidAlign& obj)
{
    size_t pos=0;
    for(size_t i=0;i<9;++i)
    {
        RotMat.add(&sPoolDbl.acquireObject(pos));
        mRotMatPos.push_back(pos);
    }
    TransMob=obj.TransMob;
    TransRigid=obj.TransRigid;
    rmsd=obj.rmsd;
    for(size_t i=0;i<9;++i)
    {
        RotMat.get(i)=obj.RotMat.get(i);
    }
}


protspace::RigidAlign&
protspace::RigidAlign::operator=(const protspace::RigidAlign& obj)
{
    if (&obj == this)return (*this);
    TransMob=obj.TransMob;
    TransRigid=obj.TransRigid;
    rmsd=obj.rmsd;
    for(size_t i=0;i<9;++i)
    {
        RotMat.get(i)=obj.RotMat.get(i);
    }
    return (*this);
}
protspace::RigidAlign::RigidAlign():
    RotMat(9,false),
    TransRigid(0,0,0),
    TransMob(0,0,0),
    rmsd(-1)
{
    size_t pos=0;
    for(size_t i=0;i<9;++i)
    {
        RotMat.add(&sPoolDbl.acquireObject(pos));
        mRotMatPos.push_back(pos);
    }
}

protspace::RigidAlign::~RigidAlign()
{
    for(size_t i=0;i<9;++i)
    {
        sPoolDbl.releaseObject(mRotMatPos.at(i));
    }
}



void protspace::RigidAlign::mobilToRef(CoordList& liste) const
{

    // Getting the mass center of the molecule to rotate, in order to calculate the translation
    // FROI the "moving center" TO this mass center.
size_t posCenter,posTranslate;
Coords& Center=sPoolCoo.acquireObject(posCenter);Center.clear();
Coords& Translate=sPoolCoo.acquireObject(posTranslate);Translate.clear();
    for(const Coords& it:liste) { Center+=it;}
    Center/=liste.size();
  //  cout << "CENTER INI : " << Center<<endl;
    Translate = Center-TransMob;
  //  cout << "TRANS INI : " << Translate<<endl;
    Translate.setxyz(Translate.x()*RotMat.get(0)+Translate.y()*RotMat.get(1)+Translate.z()*RotMat.get(2),
                        Translate.x()*RotMat.get(3)+Translate.y()*RotMat.get(4)+Translate.z()*RotMat.get(5),
                        Translate.x()*RotMat.get(6)+Translate.y()*RotMat.get(7)+Translate.z()*RotMat.get(8));
    // cout << "TRANS ROT : " << Translate<<endl;
    // Application de la rotation  sur la liste :



    for(Coords& it:liste)
    {
        it.setxyz( it.x()*RotMat.get(0)+it.y()*RotMat.get(1)+it.z()*RotMat.get(2),
                         it.x()*RotMat.get(3)+it.y()*RotMat.get(4)+it.z()*RotMat.get(5),
                         it.x()*RotMat.get(6)+it.y()*RotMat.get(7)+it.z()*RotMat.get(8));
    }
    Center.setxyz(0,0,0);
    for(const Coords& it:liste) { Center+=it;}
    Center/=liste.size();
    for(Coords& it:liste) { it+=(Translate+TransRigid-Center);}

sPoolCoo.releaseObject(posCenter);
sPoolCoo.releaseObject(posTranslate);

}

void protspace::RigidAlign::mobilToRef           ( std::vector<Coords*>& liste) const
{
    size_t posCenter,posTranslate;
    Coords& Center=sPoolCoo.acquireObject(posCenter);Center.clear();
    Coords& Translate=sPoolCoo.acquireObject(posTranslate);Translate.clear();


    for(const Coords* it:liste)Center+=*it;
    Center/=liste.size();
    std::cout << "CENTER INI : " << Center<<std::endl;
    Translate = Center-TransMob;
    std::cout << "TRANS INI : " << Translate<<std::endl;
    Translate.setxyz(Translate.x()*RotMat.get(0)+Translate.y()*RotMat.get(1)+Translate.z()*RotMat.get(2),
                        Translate.x()*RotMat.get(3)+Translate.y()*RotMat.get(4)+Translate.z()*RotMat.get(5),
                        Translate.x()*RotMat.get(6)+Translate.y()*RotMat.get(7)+Translate.z()*RotMat.get(8));
     std::cout << "TRANS ROT : " << Translate<<std::endl;

    for(Coords* it:liste)
    {
        it->setxyz(   it->x()*RotMat.get(0)+it->y()*RotMat.get(1)+it->z()*RotMat.get(2),
                         it->x()*RotMat.get(3)+it->y()*RotMat.get(4)+it->z()*RotMat.get(5),
                         it->x()*RotMat.get(6)+it->y()*RotMat.get(7)+it->z()*RotMat.get(8));
    }
    Center.setxyz(0,0,0);

    for(const Coords* it:liste){ Center+=*it;}
    Center/=liste.size();
    std::cout <<"END DIFF:"<<(Translate+TransRigid-Center)<<std::endl;
    for(Coords* it : liste) { (*it)+=(Translate+TransRigid-Center);}

    sPoolCoo.releaseObject(posCenter);
    sPoolCoo.releaseObject(posTranslate);
}


void protspace::RigidAlign::refToMobil(CoordList& liste) const
{

    // Getting the mass center of the molecule to rotate, in order to calculate the translation
    // FROI the "moving center" TO this mass center.

    size_t posCenter,posTranslate;
    Coords& Center=sPoolCoo.acquireObject(posCenter);Center.clear();
    Coords& Translate=sPoolCoo.acquireObject(posTranslate);Translate.clear();

    for(const Coords& it:liste){ Center+=it;}
    Center/=liste.size();
    Translate = TransRigid-Center;
    Translate.setxyz(
       Translate.x()*RotMat.get(0)+Translate.y()*RotMat.get(3)+Translate.z()*RotMat.get(6),
       Translate.x()*RotMat.get(1)+Translate.y()*RotMat.get(4)+Translate.z()*RotMat.get(7),
       Translate.x()*RotMat.get(2)+Translate.y()*RotMat.get(5)+Translate.z()*RotMat.get(8));

    // Application de la rotation  sur la liste :



    for(Coords& it:liste)
    {
        it.setxyz( it.x()*RotMat.get(0)+it.y()*RotMat.get(3)+it.z()*RotMat.get(6),
                         it.x()*RotMat.get(1)+it.y()*RotMat.get(4)+it.z()*RotMat.get(7),
                         it.x()*RotMat.get(2)+it.y()*RotMat.get(5)+it.z()*RotMat.get(8));

    }
    Center.setxyz(0,0,0);
    for(const Coords& it:liste) { Center+=it;}
    Center/=liste.size();
    for(Coords& it:liste) { it+=(TransMob-Center);}

    sPoolCoo.releaseObject(posCenter);
    sPoolCoo.releaseObject(posTranslate);
}

void protspace::RigidAlign::refToMobil( std::vector<Coords*>& liste) const
{

    // Getting the mass center of the molecule to rotate, in order to calculate the translation
    // FROI the "moving center" TO this mass center.

    size_t posCenter,posTranslate;
    Coords& Center=sPoolCoo.acquireObject(posCenter);Center.clear();
    Coords& Translate=sPoolCoo.acquireObject(posTranslate);Translate.clear();

    for(const Coords* it:liste){ Center+=*it;}
    Center/=liste.size();
    Translate = TransRigid-Center;
    Translate.setxyz(
       Translate.x()*RotMat.get(0)+Translate.y()*RotMat.get(3)+Translate.z()*RotMat.get(6),
       Translate.x()*RotMat.get(1)+Translate.y()*RotMat.get(4)+Translate.z()*RotMat.get(7),
       Translate.x()*RotMat.get(2)+Translate.y()*RotMat.get(5)+Translate.z()*RotMat.get(8));

    // Application de la rotation  sur la liste :


    for(Coords* it:liste)
    {
        it->setxyz(   it->x()*RotMat.get(0)+it->y()*RotMat.get(3)+it->z()*RotMat.get(6),
                         it->x()*RotMat.get(1)+it->y()*RotMat.get(4)+it->z()*RotMat.get(7),
                         it->x()*RotMat.get(2)+it->y()*RotMat.get(5)+it->z()*RotMat.get(8));
    }

    Center.setxyz(0,0,0);
    for(const Coords* it:liste){ Center+=*it;}
    Center/=liste.size();
    for(Coords* it:liste){ *it+=(TransMob-Center);}

    sPoolCoo.releaseObject(posCenter);
    sPoolCoo.releaseObject(posTranslate);
}

void protspace::RigidAlign::clear()
{
    for(size_t i=0;i<9;++i) RotMat.get(i)=0;
    TransMob.clear();
    TransRigid.clear();
    rmsd=0;
}

